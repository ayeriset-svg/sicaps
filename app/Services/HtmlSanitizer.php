<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Sanitasi HTML rich-text (mis. dari TinyMCE) dengan pendekatan allowlist,
 * untuk mencegah stored XSS saat konten dirender kembali (mahasiswa → superadmin).
 * Tidak butuh dependency eksternal (memakai DOMDocument bawaan PHP).
 */
class HtmlSanitizer
{
    /** Tag yang diperbolehkan (selain teks). */
    private const ALLOWED_TAGS = [
        'p', 'br', 'hr', 'span', 'div',
        'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'sub', 'sup', 'small', 'mark',
        'ul', 'ol', 'li', 'blockquote', 'pre', 'code',
        'a', 'img', 'figure', 'figcaption',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'caption', 'colgroup', 'col',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    ];

    /** Tag berbahaya yang dibuang total beserta isinya. */
    private const REMOVE_WITH_CHILDREN = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button',
        'textarea', 'select', 'option', 'link', 'meta', 'base', 'svg', 'math', 'template', 'noscript',
    ];

    /** Atribut yang diperbolehkan per tag. */
    private const ALLOWED_ATTRS = [
        '*'   => ['style', 'class', 'title'],
        'a'   => ['href', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height'],
        'td'  => ['colspan', 'rowspan'],
        'th'  => ['colspan', 'rowspan', 'scope'],
        'ol'  => ['start', 'type'],
        'col' => ['span'],
    ];

    public function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        // Bungkus agar encoding UTF-8 terjaga tanpa menambah <!DOCTYPE>/<html> ke output.
        $wrapped = '<?xml encoding="UTF-8"><body>' . $html . '</body>';
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $body = $dom->getElementsByTagName('body')->item(0);
        if (! $body) {
            return '';
        }

        $this->sanitizeNode($body);

        // Ambil innerHTML dari body.
        $out = '';
        foreach (iterator_to_array($body->childNodes) as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim($out);
    }

    private function sanitizeNode(DOMNode $node): void
    {
        // Iterasi salinan agar aman saat menghapus.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                continue; // teks/komentar: elemen komentar dibuang di bawah
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::REMOVE_WITH_CHILDREN, true)) {
                $child->parentNode->removeChild($child);
                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                // Tag tak dikenal: buang elemennya tapi pertahankan isinya (unwrap).
                $this->sanitizeNode($child);
                $this->unwrap($child);
                continue;
            }

            $this->sanitizeAttributes($child, $tag);
            $this->sanitizeNode($child); // rekursif ke anak
        }

        // Buang node komentar.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
            }
        }
    }

    private function sanitizeAttributes(DOMElement $el, string $tag): void
    {
        $allowed = array_merge(self::ALLOWED_ATTRS['*'], self::ALLOWED_ATTRS[$tag] ?? []);

        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);
            $value = $attr->value;

            // Buang semua event handler (on*) dan atribut tak diizinkan.
            if (str_starts_with($name, 'on') || ! in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->name);
                continue;
            }

            if ($name === 'href') {
                if (! $this->safeUrl($value)) {
                    $el->removeAttribute('href');
                } else {
                    // Paksa rel aman untuk link target=_blank.
                    $el->setAttribute('rel', 'noopener noreferrer nofollow');
                }
            }

            if ($name === 'src') {
                if (! $this->safeImageSrc($value)) {
                    $el->parentNode?->removeChild($el);
                    return;
                }
            }

            if ($name === 'style' && $this->dangerousCss($value)) {
                $el->removeAttribute('style');
            }
        }
    }

    private function safeUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }
        // Blok skema berbahaya (termasuk yang disamarkan dengan whitespace/entity).
        $scheme = strtolower(preg_replace('/\s+/', '', substr($url, 0, 20)));
        foreach (['javascript:', 'vbscript:', 'data:', 'file:'] as $bad) {
            if (str_starts_with($scheme, $bad)) {
                return false;
            }
        }

        return true;
    }

    private function safeImageSrc(string $src): bool
    {
        $src = trim($src);
        // Izinkan data URI khusus gambar (base64 dari TinyMCE) + http/https + relatif.
        if (preg_match('#^data:image/(png|jpe?g|gif|webp|bmp|svg\+xml);base64,#i', $src)) {
            // Tolak SVG base64 (bisa memuat script).
            return ! preg_match('#^data:image/svg#i', $src);
        }
        $scheme = strtolower(preg_replace('/\s+/', '', substr($src, 0, 20)));
        foreach (['javascript:', 'vbscript:', 'data:', 'file:'] as $bad) {
            if (str_starts_with($scheme, $bad)) {
                return false;
            }
        }

        return true;
    }

    private function dangerousCss(string $css): bool
    {
        $c = strtolower($css);

        return str_contains($c, 'expression(') || str_contains($c, 'javascript:')
            || str_contains($c, 'vbscript:') || str_contains($c, 'url(');
    }

    private function unwrap(DOMElement $el): void
    {
        $parent = $el->parentNode;
        if (! $parent) {
            return;
        }
        while ($el->firstChild) {
            $parent->insertBefore($el->firstChild, $el);
        }
        $parent->removeChild($el);
    }
}
