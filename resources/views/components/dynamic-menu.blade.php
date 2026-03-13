<!-- Variável global para Alpine -->
<script>
    window.menuIdsToOpen = @json($openMenuIds);
</script>

<div x-data="{
    openMenus: window.menuIdsToOpen || [],
    toggleMenu(menu) {
        if (this.openMenus.includes(menu)) {
            this.openMenus = this.openMenus.filter(m => m !== menu);
        } else {
            this.openMenus.push(menu);
        }
    }
}">
    @foreach($menus as $menu)
        @if($menu->is_divider)
            <!-- Divisor -->
            <div class="my-3 border-t border-gray-300 dark:border-gray-600"></div>
        @else
        @if($menu->children && $menu->children->count() > 0)
            <!-- Menu com Submenus -->
            <div>
                @if($menu->route_name || $menu->url)
                    <!-- Menu pai com link -->
                    <div class="flex items-center gap-1">
                        <a href="{{ $menu->url_attribute }}"
                           target="{{ $menu->target }}"
                           :class="{
                               'justify-center': sidebarCollapsed && !sidebarHovered,
                               'px-4': !sidebarCollapsed || sidebarHovered,
                               'px-2': sidebarCollapsed && !sidebarHovered
                           }"
                           class="flex-1 flex items-center py-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-300">
                            <div class="flex items-center min-w-0">
                                <span class="shrink-0" :class="{'mr-3': !sidebarCollapsed || sidebarHovered}">
                                    <x-menu-icon :icon="$menu->icon" />
                                </span>
                                <span
                                    x-show="!sidebarCollapsed || sidebarHovered"
                                    x-transition:enter="transition ease-in-out duration-300"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                    x-transition:leave="transition ease-in-out duration-200"
                                    x-transition:leave-start="opacity-100"
                                    x-transition:leave-end="opacity-0"
                                    class="whitespace-nowrap flex items-center"
                                >
                                    {{ $menu->title }}
                                    @if($menu->badge)
                                        <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded {{ $menu->badge_color ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ $menu->badge }}
                                        </span>
                                    @endif
                                </span>
                            </div>
                        </a>
                        <button @click="toggleMenu('menu_{{ $menu->id }}')"
                                x-show="!sidebarCollapsed || sidebarHovered"
                                x-transition
                                class="p-2 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-300">
                            <svg
                                class="w-4 h-4 transition-transform shrink-0"
                                :class="{ 'rotate-180': openMenus.includes('menu_{{ $menu->id }}') }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>
                @else
                    <!-- Menu pai sem link (só abre submenu) -->
                    <button @click="toggleMenu('menu_{{ $menu->id }}')"
                            :class="{
                                'justify-center': sidebarCollapsed && !sidebarHovered,
                                'px-4': !sidebarCollapsed || sidebarHovered,
                                'px-2': sidebarCollapsed && !sidebarHovered
                            }"
                            class="w-full flex items-center justify-between py-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-300">
                        <div class="flex items-center min-w-0">
                        <span class="shrink-0" :class="{'mr-3': !sidebarCollapsed || sidebarHovered}">
                            <x-menu-icon :icon="$menu->icon" />
                        </span>
                        <span
                            x-show="!sidebarCollapsed || sidebarHovered"
                            x-transition:enter="transition ease-in-out duration-300"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in-out duration-200"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="whitespace-nowrap flex items-center"
                        >
                            {{ $menu->title }}
                            @if($menu->badge)
                                <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded {{ $menu->badge_color ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $menu->badge }}
                                </span>
                            @endif
                        </span>
                    </div>
                            <svg
                                x-show="!sidebarCollapsed || sidebarHovered"
                                x-transition
                                class="w-4 h-4 transition-transform shrink-0"
                                :class="{ 'rotate-180': openMenus.includes('menu_{{ $menu->id }}') }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                @endif
                <div
                    x-show="openMenus.includes('menu_{{ $menu->id }}') && (!sidebarCollapsed || sidebarHovered)"
                    x-collapse
                    class="ml-4 mt-1 space-y-1"
                >
                    @foreach($menu->children as $submenu)
                        @php
                            $canViewSubmenu = empty($submenu->permission_name)
                                || auth()->user()?->can('access-route', $submenu->permission_name);
                        @endphp
                        @if(!$submenu->is_divider && $canViewSubmenu)
                            @php
                                $isActive = false;
                                if ($submenu->route_name) {
                                    $isActive = request()->routeIs($submenu->route_name . '*');
                                }
                            @endphp
                            <a href="{{ $submenu->url_attribute }}"
                               target="{{ $submenu->target }}"
                               @if($submenu->description) title="{{ $submenu->description }}" @endif
                               class="flex items-center px-4 py-2 text-sm text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 {{ $isActive ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
                                <span class="mr-2">
                                    <x-menu-icon :icon="$submenu->icon" class="w-4 h-4" />
                                </span>
                                {{ $submenu->title }}
                                @if($submenu->badge)
                                    <span class="ml-auto px-2 py-0.5 text-xs font-medium rounded {{ $submenu->badge_color ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $submenu->badge }}
                                    </span>
                                @endif
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @else
            <!-- Menu Simples (sem submenus) -->
            @php
                $isActive = false;
                if ($menu->route_name) {
                    $isActive = request()->routeIs($menu->route_name . '*');
                }
            @endphp
            <a href="{{ $menu->url_attribute }}"
               target="{{ $menu->target }}"
               @if($menu->description) title="{{ $menu->description }}" @endif
               :class="{
                   'justify-center': sidebarCollapsed && !sidebarHovered,
                   'px-4': !sidebarCollapsed || sidebarHovered,
                   'px-2': sidebarCollapsed && !sidebarHovered
               }"
               class="flex items-center py-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-300 {{ $isActive ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : '' }}">
                <span class="shrink-0" :class="{'mr-3': !sidebarCollapsed || sidebarHovered}">
                    <x-menu-icon :icon="$menu->icon" />
                </span>
                <span
                    x-show="!sidebarCollapsed || sidebarHovered"
                    x-transition:enter="transition ease-in-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in-out duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="whitespace-nowrap flex items-center"
                >
                    {{ $menu->title }}
                    @if($menu->badge)
                        <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded {{ $menu->badge_color ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $menu->badge }}
                        </span>
                    @endif
                    @if($menu->target === '_blank')
                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    @endif
                </span>
            </a>
        @endif
    @endif
    @endforeach
</div>
