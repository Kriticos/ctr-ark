<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Permission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->slug(),
            'description' => $this->faker->sentence(),
            'module_id' => Module::inRandomOrder()->first()?->id,
        ];
    }

    /**
     * State for index permission.
     */
    public function index(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'admin.users.index',
            'description' => 'View users list',
        ]);
    }

    /**
     * State for create permission.
     */
    public function forCreate(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'admin.users.create',
            'description' => 'Create new user',
        ]);
    }

    /**
     * State for edit permission.
     */
    public function forEdit(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'admin.users.edit',
            'description' => 'Edit user',
        ]);
    }

    /**
     * State for delete permission.
     */
    public function forDelete(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'admin.users.destroy',
            'description' => 'Delete user',
        ]);
    }
}
