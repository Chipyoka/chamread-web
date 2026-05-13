@props([
    'type' => 'button',
    'href' => null,
    'variant' => null,
    'color' => null,
    'icon' => null,
    'size' => 'md',
])

@php
    $presetColors = [
        'edit' => 'slate',
        'view' => 'blue',
        'delete' => 'red',
    ];

    $resolvedColor = $color ?? ($presetColors[$variant] ?? 'gray');

    $sizes = [
        'sm' => 'px-2 py-1.5 text-xs',
        'md' => 'px-2 py-2.5 text-sm',
        'lg' => 'px-3 py-1.5 text-sm',
    ];

    $sizeClass = $sizes[$size] ?? $sizes['sm'];

    $colorMap = [
        'gray' => 'text-gray-600 bg-gray-100 hover:shadow-sm hover:border-gray-200 border border-transparent ',
        'slate' => 'text-slate-600 bg-slate-100 hover:shadow-sm hover:border-slate-200 border border-transparent ',
        'blue' => 'text-blue-600 bg-blue-100 hover:shadow-sm hover:border-blue-200 border border-transparent ',
        'red' => 'text-red-600 bg-red-100 hover:shadow-sm hover:border-red-200 border border-transparent ',
        'green' => 'text-green-600 bg-green-100 hover:shadow-sm hover:border-green-200 border border-transparent ',
        'amber' => 'text-amber-600 bg-amber-100 hover:shadow-sm hover:border-amber-200 border border-transparent ',
        'purple' => 'text-purple-600 bg-purple-100 hover:shadow-sm hover:border-purple-200 border border-transparent ',
        'indigo' => 'text-indigo-600 bg-indigo-100 hover:shadow-sm hover:border-indigo-200 border border-transparent ',
        'pink' => 'text-pink-600 bg-pink-100 hover:shadow-sm hover:border-pink-200 border border-transparent ',
    ];

    $colorClasses = $colorMap[$resolvedColor]
        ?? $colorMap['gray'];

    $baseClass = "
        inline-flex items-center justify-center
        rounded
        font-medium
        transition-all duration-200
        whitespace-nowrap
        {$sizeClass}
        {$colorClasses}
    ";
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        {{ $attributes->merge([
            'class' => $baseClass
        ]) }}
    >
        @if($icon)
            <i data-lucide="{{ $icon }}" class="w-3.5 h-3.5 mr-1"></i>
        @endif

        <span>{{ $slot }}</span>
    </a>
@else
    <button
        type="{{ $type }}"
        {{ $attributes->merge([
            'class' => $baseClass
        ]) }}
    >
        @if($icon)
            <i data-lucide="{{ $icon }}" class="w-3.5 h-3.5 mr-1"></i>
        @endif

        <span>{{ $slot }}</span>
    </button>
@endif