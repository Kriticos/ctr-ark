<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') - {{ config('app.name') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

    <!-- Script para aplicar tema dark e sidebar ANTES de carregar CSS -->
    <script>
        // Aplica o tema e estado do sidebar imediatamente, antes de qualquer renderização
        (function() {
            const userPreference = '{{ auth()->user()->theme_preference ?? "system" }}';
            let darkMode = false;

            if (userPreference === 'dark') {
                darkMode = true;
            } else if (userPreference === 'light') {
                darkMode = false;
            } else {
                // system - detecta preferência do SO
                darkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
            }

            if (darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }

            // Inicializa o estado do sidebar para evitar flash
            const sidebarOpen = localStorage.getItem('sidebarOpen') !== 'false';
            if (!sidebarOpen) {
                document.documentElement.classList.add('sidebar-closed');
            }
        })();
    </script>

    <!-- Livewire Styles -->
    @livewireStyles

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- SortableJS para drag and drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.1/Sortable.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
</head>
<body class="bg-gray-100 dark:bg-gray-900" x-data="{
    sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false',
    sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
    sidebarHovered: false,
    darkMode: document.documentElement.classList.contains('dark'),
    saveThemePreference(isDark) {
        const theme = isDark ? 'dark' : 'light';

        fetch('{{ route('admin.profile.update-theme') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ theme: theme })
        }).catch(err => console.error('Erro ao salvar tema:', err));
    }
}" x-init="
    $watch('darkMode', val => {
        if(val) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        saveThemePreference(val);
    });
    $watch('sidebarOpen', val => {
        localStorage.setItem('sidebarOpen', val);
        if(val) {
            document.documentElement.classList.remove('sidebar-closed');
        } else {
            document.documentElement.classList.add('sidebar-closed');
        }
    });
    $watch('sidebarCollapsed', val => {
        localStorage.setItem('sidebarCollapsed', val);
    });
" @theme-toggle.window="darkMode = !darkMode">
    @php
        $currentUser = auth()->user();
        $allowedNotificationSectorIds = ($currentUser && ! $currentUser->isAdmin())
            ? $currentUser->sectors()->pluck('sectors.id')->map(fn ($id) => (int) $id)->all()
            : null;

        $notificationLabels = [
            'created' => 'Procedimento criado',
            'updated' => 'Nova versao criada',
            'submitted_for_review' => 'Enviado para aprovacao',
            'approved' => 'Procedimento aprovado',
            'rejected' => 'Procedimento reprovado',
            'published' => 'Procedimento publicado',
            'version_restored' => 'Versao restaurada',
        ];

        $notificationsQuery = \App\Models\ProcedureAudit::query()
            ->with(['procedure:id,title', 'user:id,name'])
            ->whereNotIn('action', ['viewed', 'seed_test_tutorial']);

        if ($allowedNotificationSectorIds !== null) {
            $notificationsQuery->whereHas('procedure.sectors', function ($query) use ($allowedNotificationSectorIds): void {
                $query->whereIn('sectors.id', $allowedNotificationSectorIds);
            });
        }

        $recentNotifications = $notificationsQuery->latest()->limit(5)->get();
        $notificationCount = $recentNotifications->count();
    @endphp
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside
            x-cloak
            x-show="sidebarOpen || window.innerWidth >= 1024"
            @mouseenter="if(sidebarCollapsed && window.innerWidth >= 1024) sidebarHovered = true"
            @mouseleave="if(window.innerWidth >= 1024) sidebarHovered = false"
            :class="{
                'w-64': (!sidebarCollapsed || sidebarHovered) && window.innerWidth >= 1024,
                'w-16': sidebarCollapsed && !sidebarHovered && window.innerWidth >= 1024,
                'w-64': window.innerWidth < 1024
            }"
            class="fixed lg:static inset-y-0 left-0 z-50 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col transition-all duration-300 ease-in-out"
            @click.away="if (window.innerWidth < 1024) sidebarOpen = false"
        >
            <!-- Logo -->
            <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200 dark:border-gray-700 overflow-hidden">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-2 min-w-0">
                    <svg class="w-8 h-8 text-blue-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                    </svg>
                    <span
                        x-show="!sidebarCollapsed || sidebarHovered"
                        x-transition:enter="transition ease-in-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in-out duration-300"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="text-xl font-bold text-gray-900 dark:text-white whitespace-nowrap"
                    >LaraSaas</span>
                </a>
                <button
                    @click="sidebarOpen = false"
                    x-show="!sidebarCollapsed || sidebarHovered"
                    x-transition
                    class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto p-4 space-y-1">
                <!-- Menu Dinâmico do Banco de Dados -->
                <x-dynamic-menu />
            </nav>

            <!-- User Info -->
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="flex items-center space-x-3" :class="{'justify-center': sidebarCollapsed && !sidebarHovered}">
                    <div class="relative shrink-0">
                        @if(auth()->user()->avatar)
                            <img src="{{ Storage::url(auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}" class="w-10 h-10 rounded-full object-cover">
                        @else
                            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                            </div>
                        @endif
                        <x-online-indicator :user="auth()->user()" size="md" />
                    </div>
                    <div
                        x-show="!sidebarCollapsed || sidebarHovered"
                        x-transition:enter="transition ease-in-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in-out duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="flex-1 min-w-0"
                    >
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ auth()->user()->name ?? 'Usuário' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()->email ?? 'user@example.com' }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navbar -->
            <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 h-16">
                <div class="flex items-center justify-between h-full px-4">
                    <!-- Left: Toggle Sidebar -->
                    <button @click="if (window.innerWidth >= 1024) { sidebarCollapsed = !sidebarCollapsed } else { sidebarOpen = !sidebarOpen }" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <!-- Right: Actions -->
                    <div class="flex items-center space-x-4">
                        <!-- Search -->
                        <div class="hidden md:block">
                            <input type="search"
                                   placeholder="Buscar..."
                                   class="px-4 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-sm text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Dark Mode Toggle -->
                        <button @click="darkMode = !darkMode"
                                class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-150 active:scale-95">
                            <svg x-show="!darkMode" x-transition class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                            <svg x-show="darkMode" x-transition class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </button>

                        <!-- Notifications Dropdown -->
                        <flux:dropdown align="right">
                            <flux:button variant="ghost" class="relative">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                @if($notificationCount > 0)
                                    <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white dark:ring-gray-800"></span>
                                @endif
                            </flux:button>

                            <flux:menu class="w-80">
                                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Notificações</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $notificationCount }} {{ \Illuminate\Support\Str::plural('movimentacao recente', $notificationCount) }}
                                    </p>
                                </div>
                                @forelse($recentNotifications as $notification)
                                    <flux:menu.item href="{{ $notification->procedure ? route('admin.procedures.show', $notification->procedure) : route('admin.dashboard') }}">
                                        <div class="flex flex-col space-y-1">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $notificationLabels[$notification->action] ?? ucfirst(str_replace('_', ' ', $notification->action)) }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                {{ $notification->procedure?->title ?? 'Procedimento indisponivel' }} • {{ $notification->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                    </flux:menu.item>
                                @empty
                                    <div class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        Nenhuma notificacao recente.
                                    </div>
                                @endforelse
                                <flux:separator />
                                <flux:menu.item href="{{ route('admin.dashboard') }}">
                                    <span class="text-sm text-blue-600 dark:text-blue-400">Ver painel completo</span>
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>

                        <!-- User Dropdown -->
                        <flux:dropdown align="right">
                            <flux:button variant="ghost">
                                <div class="flex items-center space-x-2">
                                    <div class="relative">
                                        @if(auth()->user()->avatar)
                                            <img src="{{ Storage::url(auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}" class="w-8 h-8 rounded-full object-cover">
                                        @else
                                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                                            </div>
                                        @endif
                                        <x-online-indicator :user="auth()->user()" size="sm" />
                                    </div>
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </flux:button>

                            <flux:menu>
                                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ auth()->user()->name ?? 'Usuário' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->email ?? 'user@example.com' }}</p>
                                </div>
                                <flux:menu.item href="{{ route('admin.profile.edit') }}">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    Meu Perfil
                                </flux:menu.item>
                                <flux:menu.item>
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Configurações
                                </flux:menu.item>
                                <flux:separator />
                                <flux:menu.item>
                                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                                        @csrf
                                        <button type="submit" class="flex items-center w-full text-left">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                            </svg>
                                            Sair
                                        </button>
                                    </form>
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 dark:bg-gray-900">
                <div class="@yield('content_wrapper_class', 'container mx-auto px-4 py-6')">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- Overlay para fechar sidebar no mobile -->
    <div x-show="sidebarOpen && window.innerWidth < 1024"
         @click="sidebarOpen = false"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-600 bg-opacity-75 z-40 lg:hidden"
    ></div>

    @fluxScripts

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @if(session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Sucesso!',
            text: "{{ session('success') }}",
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    </script>
    @endif

    @if(session('error'))
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: "{{ session('error') }}",
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    </script>
    @endif

    @if(session('warning'))
    <script>
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: "{{ session('warning') }}",
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    </script>
    @endif

    @if(session('info'))
    <script>
        Swal.fire({
            icon: 'info',
            title: 'Informação',
            text: "{{ session('info') }}",
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    </script>
    @endif

    @stack('scripts')

    <!-- Livewire Scripts -->
    @livewireScripts
</body>
</html>
