@if ($paginator->hasPages())
    <nav class="flex items-center gap-3 flex-wrap text-sm" role="navigation">
        <span class="text-xs text-slate-500">
            Menampilkan <span class="font-medium text-slate-700">{{ $paginator->firstItem() ?? 0 }}</span>–<span class="font-medium text-slate-700">{{ $paginator->lastItem() ?? 0 }}</span>
            dari <span class="font-medium text-slate-700">{{ $paginator->total() }}</span>
        </span>

        <div class="flex items-center gap-1">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="h-8 w-8 flex items-center justify-center rounded-lg text-slate-300 cursor-default">‹</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="h-8 w-8 flex items-center justify-center rounded-lg text-brand hover:bg-rose-50 transition">‹</a>
            @endif

            {{-- Page numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="h-8 px-2 flex items-center justify-center text-slate-400">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="h-8 min-w-[2rem] px-2 flex items-center justify-center rounded-lg bg-brand text-white font-semibold shadow-sm">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="h-8 min-w-[2rem] px-2 flex items-center justify-center rounded-lg text-slate-600 hover:bg-rose-50 hover:text-brand transition">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="h-8 w-8 flex items-center justify-center rounded-lg text-brand hover:bg-rose-50 transition">›</a>
            @else
                <span class="h-8 w-8 flex items-center justify-center rounded-lg text-slate-300 cursor-default">›</span>
            @endif
        </div>
    </nav>
@else
    @if ($paginator->total() > 0)
        <span class="text-xs text-slate-500">Menampilkan {{ $paginator->total() }} data.</span>
    @endif
@endif
