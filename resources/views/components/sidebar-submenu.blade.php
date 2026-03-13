@props(['title', 'icon', 'menuId', 'active' => false])

<div>
    <button
        @click="toggleMenu('{{ $menuId }}')"
        :class="{
            'justify-center': $root.sidebarCollapsed && !$root.sidebarHovered,
            'px-4': !$root.sidebarCollapsed || $root.sidebarHovered,
            'px-2': $root.sidebarCollapsed && !$root.sidebarHovered
        }"
        class="w-full flex items-center justify-between py-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-300"
    >
        <div class="flex items-center min-w-0">
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
            >{{ $title }}</span>
        </div>
        <svg
            x-show="!$root.sidebarCollapsed || $root.sidebarHovered"
            x-transition
            class="w-4 h-4 transition-transform shrink-0"
            :class="{ 'rotate-180': openMenus.includes('{{ $menuId }}') }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    <div
        x-show="openMenus.includes('{{ $menuId }}') && (!$root.sidebarCollapsed || $root.sidebarHovered)"
        x-collapse
        class="ml-4 mt-1 space-y-1"
    >
        {{ $slot }}
    </div>
</div>
