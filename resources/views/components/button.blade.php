@props(['href' => null, 'variant' => 'primary', 'size' => 'md', 'type' => 'button', 'block' => false])

@php
    $variants = ['primary', 'secondary', 'ghost', 'danger', 'link'];
    $sizes = ['sm', 'md', 'lg'];
    $variant = in_array($variant, $variants, true) ? $variant : 'primary';
    $size = in_array($size, $sizes, true) ? $size : 'md';

    $classes = trim(sprintf(
        'btn btn-%s%s%s',
        $variant,
        $size !== 'md' ? ' btn-'.$size : '',
        $block ? ' btn-block' : ''
    ));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
