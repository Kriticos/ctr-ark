<?php

namespace Database\Factories;

use App\Models\Menu;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Menu>
 */
class MenuFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Menu::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->word(),
            'icon' => 'fas fa-cube',
            'url' => null,
            'route_name' => null,
            'permission_name' => null,
            'order' => $this->faker->numberBetween(1, 100),
            'is_active' => true,
            'is_divider' => false,
            'parent_id' => null,
            'module_id' => Module::inRandomOrder()->first()?->id,
        ];
    }

    /**
     * State for parent menu.
     */
    public function parent(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => null,
            'route_name' => null,
            'url' => null,
        ]);
    }

    /**
     * State for child menu.
     */
    public function child(Menu $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
        ]);
    }

    /**
     * State for divider menu.
     */
    public function divider(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_divider' => true,
            'icon' => null,
            'route_name' => null,
            'url' => null,
        ]);
    }

    /**
     * State for inactive menu.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * State for menu with URL.
     */
    public function withUrl(string $url): static
    {
        return $this->state(fn (array $attributes) => [
            'url' => $url,
            'route_name' => null,
        ]);
    }

    /**
     * State for menu with route name.
     */
    public function withRouteName(string $routeName): static
    {
        return $this->state(fn (array $attributes) => [
            'route_name' => $routeName,
            'url' => null,
        ]);
    }

    /**
     * State for menu with permission.
     */
    public function withPermission(string $permissionName): static
    {
        return $this->state(fn (array $attributes) => [
            'permission_name' => $permissionName,
        ]);
    }
}
