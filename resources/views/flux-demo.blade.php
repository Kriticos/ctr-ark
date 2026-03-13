<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flux Demo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="bg-gray-50 dark:bg-gray-900 p-8">
    <div class="max-w-4xl mx-auto space-y-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">Flux UI Demo (Free Components)</h1>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Buttons</h2>
            <div class="flex flex-wrap gap-4">
                <flux:button>Primary Button</flux:button>
                <flux:button variant="danger">Danger Button</flux:button>
                <flux:button variant="ghost">Ghost Button</flux:button>
                <flux:button variant="outline">Outline Button</flux:button>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Icons</h2>
            <div class="flex flex-wrap gap-4">
                <flux:icon.home class="size-6" />
                <flux:icon.user class="size-6" />
                <flux:icon.cog class="size-6" />
                <flux:icon.check class="size-6" />
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Dropdown</h2>
            <flux:dropdown>
                <flux:button>Open Menu</flux:button>
                <flux:menu>
                    <flux:menu.item>Profile</flux:menu.item>
                    <flux:menu.item>Settings</flux:menu.item>
                    <flux:separator />
                    <flux:menu.item>Logout</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Tooltips</h2>
            <div class="flex gap-4">
                <flux:tooltip content="This is a tooltip">
                    <flux:button>Hover me</flux:button>
                </flux:tooltip>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                ℹ️ Componentes como Card, Input, Textarea e outros fazem parte do Flux Pro.
                <a href="https://fluxui.dev/pricing" class="text-blue-600 hover:underline" target="_blank">Veja os planos</a>
            </p>
        </div>
    </div>

    @fluxScripts
</body>
</html>
