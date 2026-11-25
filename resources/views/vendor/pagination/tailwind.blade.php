@if ($paginator->hasPages())
    @php
        $enlaces = $paginator->onEachSide(1)->linkCollection()->slice(1, -1);
    @endphp

    <nav role="navigation" aria-label="Paginación de resultados">
        <div class="z-pagination">
            <p class="z-pagination-info">
                Mostrando
                <span class="font-semibold text-ink">{{ $paginator->firstItem() ?? 0 }}</span>
                a
                <span class="font-semibold text-ink">{{ $paginator->lastItem() ?? 0 }}</span>
                de
                <span class="font-semibold text-ink">{{ $paginator->total() }}</span>
                registros
            </p>

            <div class="z-pagination-nav">
                @if ($paginator->onFirstPage())
                    <span class="z-pagination-item" aria-disabled="true" aria-label="Página anterior">
                        <x-z.icon name="chevron-down" class="size-4 rotate-90" />
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="z-pagination-item" aria-label="Página anterior">
                        <x-z.icon name="chevron-down" class="size-4 rotate-90" />
                    </a>
                @endif

                @foreach ($enlaces as $enlace)
                    @if (is_null($enlace['url']))
                        <span class="z-pagination-item" aria-hidden="true">…</span>
                    @elseif ($enlace['active'])
                        <span class="z-pagination-item" aria-current="page">{{ $enlace['label'] }}</span>
                    @else
                        <a href="{{ $enlace['url'] }}" class="z-pagination-item">{{ $enlace['label'] }}</a>
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="z-pagination-item" aria-label="Página siguiente">
                        <x-z.icon name="chevron-down" class="size-4 -rotate-90" />
                    </a>
                @else
                    <span class="z-pagination-item" aria-disabled="true" aria-label="Página siguiente">
                        <x-z.icon name="chevron-down" class="size-4 -rotate-90" />
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
