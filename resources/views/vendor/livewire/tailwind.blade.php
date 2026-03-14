@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">

            {{-- 📱 Mobile Pagination (Small screens) --}}
            <div class="flex justify-between flex-1 sm:hidden">
                {{-- Previous Button --}}
                <span>
                    @if ($paginator->onFirstPage())
                        <flux:button

                            disabled
                            class="relative inline-flex items-center px-4 py-2 text-sm font-normal leading-5 rounded-md dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-300"
                        >
                            {!! __('pagination.previous') !!}
                        </flux:button>
                    @else
                        <flux:button
                            
                            wire:click="previousPage('{{ $paginator->getPageName() }}')"
                            x-on:click="{{ $scrollIntoViewJsSnippet }}"
                            wire:loading.attr="disabled"
                            dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before"
                            class="relative inline-flex items-center px-4 py-2 text-sm font-normal leading-5 rounded-md transition ease-in-out duration-150"
                        >
                            {!! __('pagination.previous') !!}
                        </flux:button>
                    @endif
                </span>

                {{-- Next Button --}}
                <span>
                    @if ($paginator->hasMorePages())
                        <flux:button

                            type="button"
                            wire:click="nextPage('{{ $paginator->getPageName() }}')"
                            x-on:click="{{ $scrollIntoViewJsSnippet }}"
                            wire:loading.attr="disabled"
                            dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before"
                            class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-normal leading-5 rounded-md transition ease-in-out duration-150"
                        >
                            {!! __('pagination.next') !!}
                        </flux:button>
                    @else
                        <flux:button

                            disabled
                            class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-normal leading-5 rounded-md dark:text-zinc-600 dark:bg-zinc-800 dark:border-zinc-600"
                        >
                            {!! __('pagination.next') !!}
                        </flux:button>
                    @endif
                </span>
            </div>

            {{-- 🖥️ Desktop Pagination (Large screens) --}}
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">

                {{-- Links Block using button.group --}}
                <flux:button.group >

                    {{-- Previous Page Link (Icon) --}}
                    @if ($paginator->onFirstPage())
                        <flux:button

                            disabled
                            icon="chevron-left"
                        />
                    @else
                        <flux:button

                            type="button"
                            wire:click="previousPage('{{ $paginator->getPageName() }}')"
                            x-on:click="{{ $scrollIntoViewJsSnippet }}"
                            dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after"
                            icon="chevron-left"
                        />
                    @endif

                    {{-- Pagination Elements (Page Numbers) --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <flux:button

                                disabled
                                class="cursor-default font-normal"
                            >
                                {{ $element }}
                            </flux:button>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                                    @if ($page == $paginator->currentPage())
                                        <flux:button class="font-normal"

                                            variant="primary" {{-- PÁGINA ACTIVA CON VARIANTE PRIMARY --}}
                                            aria-current="page"
                                            disabled
                                        >
                                            {{ $page }}
                                        </flux:button>
                                    @else
                                        <flux:button class="font-normal"

                                            type="button"
                                            wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                            x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                            aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                        >
                                            {{ $page }}
                                        </flux:button>
                                    @endif
                                </span>
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link (Icon) --}}
                    @if ($paginator->hasMorePages())
                        <flux:button

                            type="button"
                            wire:click="nextPage('{{ $paginator->getPageName() }}')"
                            x-on:click="{{ $scrollIntoViewJsSnippet }}"
                            dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after"
                            icon="chevron-right"
                        />
                    @else
                        <flux:button

                            disabled
                            icon="chevron-right"
                        />
                    @endif

                </flux:button.group>
            </div>
        </nav>
    @endif
</div>
