@blaze(fold: true)

@props([
    'name' => $attributes->whereStartsWith('wire:model')->first(),
])

@php
$invalid = $name && $errors->has($name);
$classes = Flux::classes()
    ->add('flex size-[1.125rem] rounded-full mt-px outline-offset-2')
    ->add($invalid ? 'border-red-500 text-red-500' : '');
@endphp

<flux:with-inline-field variant="inline" :$attributes>
    {{-- We have to put tabindex="-1" here because otherwise, Livewire requests will wipe out tabindex state, --}}
    {{-- even with durable attributes for some reason... --}}
    {{-- We are redundantly setting the size of this container to 1.125rem so that the focus outline isn't oblong. --}}
    <ui-radio {{ $attributes->class($classes) }} data-flux-control data-flux-radio tabindex="-1">
        <flux:radio.indicator />
    </ui-radio>
</flux:with-inline-field>
