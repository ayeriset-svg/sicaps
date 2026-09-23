@props(['name', 'class' => 'w-4 h-4'])
{{-- Ikon garis SVG (lihat App\Support\Icon). Gunakan class="ico" untuk ikon sebaris dengan teks. --}}
{!! \App\Support\Icon::svg($name, $class) !!}
