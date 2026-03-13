<?php

namespace App\View\Components;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

class DynamicMenu extends Component
{
    /** @var Collection<int, Menu> */
    public Collection $menus;

    /** @var array<int, string> */
    public array $openMenuIds;

    public function __construct()
    {
        $this->menus = $this->getMenusForUser();
        $this->openMenuIds = $this->getOpenMenuIds();
    }

    /**
     * Busca os menus filtrados por permissão.
     *
     * @return Collection<int, Menu>
     */
    protected function getMenusForUser(): Collection
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        /** @var Collection<int, Menu> $allMenus */
        $allMenus = Menu::with(['module', 'children' => function ($query) {
            $query->where('is_active', true)->orderBy('order');
        }])
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        return $allMenus->filter(fn (Menu $menu) => $this->userHasAccessToMenu($menu, $user));
    }

    /**
     * Verifica permissão de acesso.
     */
    protected function userHasAccessToMenu(Menu $menu, User $user): bool
    {
        // Se for um divisor, sempre retorna true
        if ($menu->is_divider) {
            return true;
        }

        // Se houver uma permissão associada, verifica se o usuário a possui
        if (! empty($menu->permission_name)) {
            return $user->can('access-route', $menu->permission_name);
        }

        // Se não tiver permissão definida, verifica se algum filho dá acesso
        // Se não tiver filhos (vazio), o padrão é retornar true (conforme seu original)
        return $menu->children->isEmpty()
        || $menu->children->contains(fn (Menu $child) => $this->userHasAccessToMenu($child, $user));
    }

    /**
     * Define quais menus pais devem iniciar abertos.
     *
     * @return array<int, string>
     */
    protected function getOpenMenuIds(): array
    {
        $currentRoute = request()->route()?->getName();
        $currentPath = request()->path();

        return $this->menus
            ->filter(fn (Menu $menu) => $this->isMenuOrChildActive($menu, $currentRoute, $currentPath))
            ->map(fn (Menu $menu) => 'menu_'.$menu->id)
            ->values()
            ->toArray();
    }

    /**
     * Verifica se o menu ou algum de seus filhos está ativo.
     */
    private function isMenuOrChildActive(Menu $menu, ?string $currentRoute, string $currentPath): bool
    {
        if ($menu->children->isEmpty()) {
            return false;
        }

        return $menu->children->contains(function (Menu $child) use ($currentRoute, $currentPath) {
            return $this->checkRouteMatch($child, $currentRoute) ||
                   $this->checkPathMatch($child, $currentPath);
        });
    }

    /**
     * Valida correspondência de nomes de rota.
     */
    private function checkRouteMatch(Menu $menu, ?string $currentRoute): bool
    {
        if (! $menu->route_name || ! $currentRoute) {
            return false;
        }

        if (str_starts_with($currentRoute, $menu->route_name)) {
            return true;
        }

        $parts = explode('.', $menu->route_name);
        $prefix = implode('.', array_slice($parts, 0, -1));

        return $prefix !== '' && str_starts_with($currentRoute, $prefix);
    }

    /**
     * Valida correspondência de URL/Path.
     */
    private function checkPathMatch(Menu $menu, string $currentPath): bool
    {
        if (! $menu->url) {
            return false;
        }

        $targetPath = ltrim($menu->url, '/');

        return $targetPath !== '' && str_starts_with($currentPath, $targetPath);
    }

    public function render(): View
    {
        return view('components.dynamic-menu');
    }
}
