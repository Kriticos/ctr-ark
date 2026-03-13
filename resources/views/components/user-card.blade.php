@props(['user', 'showLink' => true])

<div class="flex items-center space-x-4 p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:shadow-md transition-shadow">
    <!-- Avatar -->
    <div class="relative shrink-0">
        @if($user->avatar)
            <img src="{{ asset('storage/' . $user->avatar) }}"
                 alt="{{ $user->name }}"
                 class="w-12 h-12 rounded-full object-cover">
        @else
            <div class="w-12 h-12 rounded-full bg-linear-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold text-lg">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
        @endif

        <!-- Status Indicator -->
        @php
            $isOnline = $user->updated_at->diffInMinutes(now()) <= 5;
        @endphp
        <span class="absolute bottom-0 right-0 block h-3 w-3 rounded-full ring-2 ring-white dark:ring-gray-800 {{ $isOnline ? 'bg-green-500' : 'bg-gray-400' }}"></span>
    </div>

    <!-- User Info -->
    <div class="flex-1 min-w-0">
        <div class="flex items-center space-x-2">
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                {{ $user->name }}
            </p>
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $isOnline ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                {{ $isOnline ? 'Online' : 'Offline' }}
            </span>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
            {{ $user->email }}
        </p>
    </div>

    <!-- Actions -->
    @if($showLink)
        @can('access-route', 'admin.users.show')
            <div class="shrink-0">
                <a href="{{ route('admin.users.show', $user) }}"
                   class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Ver perfil
                </a>
            </div>
        @endcan
    @endif
</div>
