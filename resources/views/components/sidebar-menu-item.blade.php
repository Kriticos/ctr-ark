@props(['route' => null, 'active' => false, 'icon'])

<a
    {{ $attributes->merge(['href' => $route ? route($route) : '#']) }}
    :class="{
        'justify-center': $root.sidebarCollapsed && !$root.sidebarHovered,
        'px-4': !$root.sidebarCollapsed || $root.sidebarHovered,
        'px-2': $root.sidebarCollapsed && !$root.sidebarHovered
    }"
    class="flex items-center py-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-300 {{ $active ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : '' }}"
>
    <div class="shrink-0" :class="{'mr-3': !$root.sidebarCollapsed || $root.sidebarHovered}">
        {!! $icon !!}
    </div>
    <span
        x-show="!$root.sidebarCollapsed || $root.sidebarHovered"
        x-transition:enter="transition ease-in-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in-out duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="whitespace-nowrap"
    >
        {{ $slot }}
    </span>
</a>
