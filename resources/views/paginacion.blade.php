@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-2 flex-wrap pt-4" role="navigation" aria-label="Paginación">
        <div class="text-xs text-slate-500">
            Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }} ({{ $paginator->total() }} registros)
        </div>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="btn btn-sm btn-secondary opacity-50 cursor-not-allowed">Anterior</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-sm btn-secondary">Anterior</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-xs text-slate-400">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="btn btn-sm btn-primary">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="btn btn-sm btn-secondary">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-sm btn-secondary">Siguiente</a>
            @else
                <span class="btn btn-sm btn-secondary opacity-50 cursor-not-allowed">Siguiente</span>
            @endif
        </div>
    </nav>
@endif
