@props(['name', 'value' => '', 'disabled' => false, 'minHeight' => 520])

<textarea
    name="{{ $name }}"
    class="richtext-editor"
    data-min="{{ $minHeight }}"
    @if($disabled) data-readonly="1" @endif
    rows="10"
    style="width:100%">{{ $value }}</textarea>

@once
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    (function () {
        function initOne(el) {
            var minH = parseInt(el.dataset.min, 10) || 520;
            tinymce.init({
                target: el,
                license_key: 'gpl',
                promotion: false,
                branding: false,
                menubar: false,
                statusbar: true,
                min_height: minH,
                autoresize_bottom_margin: 30,
                plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen table wordcount autoresize',
                toolbar: 'undo redo | blocks fontfamily fontsizeinput | bold italic underline strikethrough subscript superscript | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | blockquote link image table charmap | removeformat code fullscreen',
                toolbar_mode: 'wrap',
                font_size_formats: '10px 11px 12px 14px 16px 18px 20px 24px 30px 36px',
                font_family_formats: 'Plus Jakarta Sans=Plus Jakarta Sans,sans-serif; Arial=arial,helvetica,sans-serif; Times New Roman=times new roman,times,serif; Calibri=calibri,sans-serif; Courier New=courier new,courier,monospace',
                image_caption: true,
                automatic_uploads: false,
                paste_data_images: true,
                convert_urls: false,
                file_picker_types: 'image',
                file_picker_callback: function (cb) {
                    var input = document.createElement('input');
                    input.type = 'file';
                    input.accept = 'image/*';
                    input.onchange = function () {
                        var file = this.files[0];
                        if (!file) return;
                        var reader = new FileReader();
                        reader.onload = function () { cb(reader.result, { alt: file.name }); };
                        reader.readAsDataURL(file);
                    };
                    input.click();
                },
                content_style:
                    "html{background:#eef0f2;padding:18px 0;}" +
                    "body{background:#fff;max-width:820px;margin:0 auto;padding:40px 56px;" +
                    "box-shadow:0 2px 14px rgba(0,0,0,.12);font-family:'Plus Jakarta Sans',Arial,sans-serif;" +
                    "font-size:14px;line-height:1.65;color:#1f2937;}" +
                    "img{max-width:100%;height:auto;}" +
                    "table{border-collapse:collapse;}table td,table th{border:1px solid #cbd5e1;padding:6px 8px;}" +
                    "blockquote{border-left:3px solid #e5a3a3;margin-left:0;padding-left:12px;color:#6b7280;}",
                setup: function (editor) {
                    editor.on('init', function () {
                        if (editor.getElement().dataset.readonly === '1') {
                            editor.mode.set('readonly');
                        }
                    });
                    // Sinkronkan konten ke <textarea> terus-menerus agar submit selalu terbaru.
                    editor.on('change keyup input undo redo SetContent', function () { editor.save(); });
                }
            });
        }
        function initEditors() {
            if (!window.tinymce) return;
            document.querySelectorAll('textarea.richtext-editor').forEach(initOne);
        }
        if (window.tinymce) { initEditors(); }
        else { document.addEventListener('DOMContentLoaded', initEditors); }
    })();
</script>
@endpush
@endonce
