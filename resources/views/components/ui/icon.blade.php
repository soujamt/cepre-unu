@props (['name', 'size' => '5', 'class' => ''])

<span {{ $attributes->merge(['class' => "inline-block w-$size h-$size " . $class]) }}>
    @includeIf ("components.icons.{$name}")
</span>
