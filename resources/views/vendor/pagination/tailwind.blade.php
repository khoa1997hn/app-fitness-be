@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <ul class="list-none">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="inline-block">
                    <span class="flex items-center justify-center w-6 h-6 bg-slate-100 dark:bg-slate-700 text-slate-400 dark:text-slate-500 rounded mx-[3px] sm:mx-1 text-sm font-Inter font-medium cursor-not-allowed relative top-[2px] pl-2" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                        <iconify-icon icon="material-symbols:arrow-back-ios-rounded"></iconify-icon>
                    </span>
                </li>
            @else
                <li class="inline-block">
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}" class="flex items-center justify-center w-6 h-6 bg-slate-100 dark:bg-slate-700 dark:hover:bg-black-500 text-slate-800 dark:text-white rounded mx-[3px] sm:mx-1 hover:bg-black-500 hover:text-white text-sm font-Inter font-medium transition-all duration-300 relative top-[2px] pl-2">
                        <iconify-icon icon="material-symbols:arrow-back-ios-rounded"></iconify-icon>
                    </a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="inline-block">
                        <span class="flex items-center justify-center w-6 h-6 bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-white rounded mx-[3px] sm:mx-1 text-sm font-Inter font-medium">{{ $element }}</span>
                    </li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="inline-block" aria-current="page">
                                <a href="#" class="flex items-center justify-center w-6 h-6 bg-slate-100 text-slate-800 dark:text-white rounded mx-[3px] sm:mx-1 hover:bg-black-500 hover:text-white text-sm font-Inter font-medium transition-all duration-300 p-active">{{ $page }}</a>
                            </li>
                        @else
                            <li class="inline-block">
                                <a href="{{ $url }}" class="flex items-center justify-center w-6 h-6 bg-slate-100 dark:bg-slate-700 dark:hover:bg-black-500 text-slate-800 dark:text-white rounded mx-[3px] sm:mx-1 hover:bg-black-500 hover:text-white text-sm font-Inter font-medium transition-all duration-300" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="inline-block">
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}" class="flex items-center justify-center w-6 h-6 bg-slate-100 dark:bg-slate-700 dark:hover:bg-black-500 text-slate-800 dark:text-white rounded mx-[3px] sm:mx-1 hover:bg-black-500 hover:text-white text-sm font-Inter font-medium transition-all duration-300 relative top-[2px]">
                        <iconify-icon icon="material-symbols:arrow-forward-ios-rounded"></iconify-icon>
                    </a>
                </li>
            @else
                <li class="inline-block">
                    <span class="flex items-center justify-center w-6 h-6 bg-slate-100 dark:bg-slate-700 text-slate-400 dark:text-slate-500 rounded mx-[3px] sm:mx-1 text-sm font-Inter font-medium cursor-not-allowed relative top-[2px]" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                        <iconify-icon icon="material-symbols:arrow-forward-ios-rounded"></iconify-icon>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
