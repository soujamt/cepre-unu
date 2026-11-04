@props ([
    'align' => 'start',
])

@php
    $alignmentClasses = match ($align) {
        'center' => 'text-center',
        'end' => 'text-end',
        default => 'text-start',
    };

    $customClasses = (string) $attributes->get('class', '');
    $whitespaceClasses = str_contains($customClasses, 'whitespace-') ? '' : 'whitespace-nowrap';
    $heightClasses = preg_match('/(^|\s)h-/', $customClasses) ? '' : 'h-12';
@endphp

<td {{ $attributes->class(["{$heightClasses} px-3 first:ps-4 last:pe-4 {$alignmentClasses} {$whitespaceClasses} text-sm font-normal text-zinc-700 dark:text-zinc-300"]) }}>
    {{ $slot }}
</td>
