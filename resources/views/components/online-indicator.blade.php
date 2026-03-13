@props(['user', 'size' => 'md'])

@php
    $sizes = [
        'sm' => 'w-2 h-2 bottom-0 right-0',
        'md' => 'w-2.5 h-2.5 bottom-0 right-0',
        'lg' => 'w-3 h-3 bottom-0.5 right-0.5',
    ];

    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $isOnline = $user->isOnline();
@endphp

@if($isOnline)
<span class="absolute block {{ $sizeClass }} rounded-full bg-green-500 border-2 border-white dark:border-gray-800" title="Online"></span>
@endif
