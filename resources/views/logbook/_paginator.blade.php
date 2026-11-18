{{-- Paginator cetak: memecah .sheet menjadi lembaran A4 (.page) & menomori tiap halaman di
     kanan bawah. Berjalan di browser saat halaman cetak dibuka (tanpa dependensi eksternal). --}}
<script>
(function () {
    function px(mm) { return mm * 96 / 25.4; }
    var LIMIT = px(297 - 30); // tinggi area isi A4 = tinggi kertas - (margin atas 15mm + bawah 15mm)

    function paginate() {
        var source = document.querySelector('.sheet');
        if (!source || document.getElementById('print-pages')) return;

        var blocks = Array.prototype.slice.call(source.children);
        var container = document.createElement('div');
        container.id = 'print-pages';
        // Sisipkan dulu ke DOM agar pengukuran tinggi (scrollHeight) akurat.
        source.parentNode.insertBefore(container, source);

        function newPage() {
            var p = document.createElement('div'); p.className = 'page';
            var b = document.createElement('div'); b.className = 'page-body';
            p.appendChild(b); container.appendChild(p); return b;
        }

        var body = newPage();
        blocks.forEach(function (blk) {
            body.appendChild(blk);
            // Bila melebihi tinggi satu halaman, pindahkan blok ini ke halaman baru.
            if (body.scrollHeight > LIMIT && body.children.length > 1) {
                body.removeChild(blk);
                body = newPage();
                body.appendChild(blk);
            }
        });

        source.parentNode.removeChild(source);

        var pages = container.querySelectorAll('.page');
        for (var i = 0; i < pages.length; i++) {
            var n = document.createElement('div'); n.className = 'page-num';
            n.textContent = 'Hal. ' + (i + 1) + ' / ' + pages.length;
            pages[i].appendChild(n);
        }
    }

    if (document.readyState === 'complete') { paginate(); }
    else { window.addEventListener('load', paginate); }
})();
</script>
