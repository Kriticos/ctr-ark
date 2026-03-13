<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'module_id',
        'parent_id',
        'title',
        'icon',
        'route_name',
        'url',
        'permission_name',
        'order',
        'is_active',
        'is_divider',
        'target',
        'badge',
        'badge_color',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_divider' => 'boolean',
    ];

    /**
     * Relacionamento com Module.
     *
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Relacionamento com menu pai.
     *
     * @return BelongsTo<Menu, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    /**
     * Relacionamento com menus filhos.
     *
     * @return HasMany<Menu, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('order');
    }

    /**
     * @param  Builder<Menu>  $query
     * @return Builder<Menu>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para buscar apenas menus principais (sem pai).
     *
     * @param  Builder<Menu>  $query
     * @return Builder<Menu>
     */
    public function scopeMainMenus(Builder $query): Builder
    {
        return $query->whereNull('parent_id')->orderBy('order');
    }

    /**
     * Scope para buscar apenas submenus.
     *
     * @param  Builder<Menu>  $query
     * @return Builder<Menu>
     */
    public function scopeSubmenus(Builder $query): Builder
    {
        return $query->whereNotNull('parent_id')->orderBy('order');
    }

    /**
     * Verifica se o menu tem filhos.
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Retorna a URL do menu.
     */
    public function getUrlAttribute(?string $value): ?string
    {
        if ($this->route_name) {
            return route($this->route_name);
        }

        return $value;
    }

    /**
     * Retorna a URL calculada para uso nos links.
     */
    public function getUrlAttributeAttribute(): ?string
    {
        if ($this->route_name && \Illuminate\Support\Facades\Route::has($this->route_name)) {
            return route($this->route_name);
        }

        return $this->attributes['url'] ?? '#';
    }

    /**
     * Verifica se é um divisor.
     */
    public function isDivider(): bool
    {
        return $this->is_divider;
    }

    /**
     * Retorna menus em árvore hierárquica.
     *
     * @return EloquentCollection<int, static>
     */
    public static function getMenuTree(): EloquentCollection
    {
        return static::with(['children' => function ($query) {
            $query->active()->orderBy('order');
        }])
            ->active()
            ->mainMenus()
            ->get();
    }
}
