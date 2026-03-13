<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $avatar
 * @property \Illuminate\Support\Carbon|null $last_activity_at
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property bool $is_online
 * @property string|null $theme_preference
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 */
class User extends Authenticatable implements CanResetPassword
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use CanResetPasswordTrait, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'last_activity_at',
        'last_login_at',
        'is_online',
        'theme_preference',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_activity_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_online' => 'boolean',
        ];
    }

    /**
     * Verifica se o usuário está online
     * Considera online se teve atividade nos últimos 5 minutos.
     */
    public function isOnline(): bool
    {
        if (! $this->last_activity_at) {
            return false;
        }

        return $this->last_activity_at->gt(now()->subMinutes(5));
    }

    /**
     * Retorna usuários online
     * Considera online quem teve atividade nos últimos 5 minutos.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\User>
     */
    public static function onlineUsers(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('last_activity_at', '>=', now()->subMinutes(5))
            ->orderBy('last_activity_at', 'desc')
            ->get();
    }

    /**
     * Retorna o texto de status do usuário.
     */
    public function getStatusText(): string
    {
        $status = 'Offline';

        if ($this->isOnline()) {
            $status = 'Online';
        } elseif (! $this->last_login_at) {
            $status = 'Nunca ativo';
        } elseif ($this->last_activity_at) {
            $status = $this->calculateRecentActivityStatus();
        }

        return $status;
    }

    /**
     * Método auxiliar para reduzir a complexidade e os pontos de saída.
     */
    private function calculateRecentActivityStatus(): string
    {
        // Se faz mais de 7 dias
        if ($this->last_activity_at->lt(now()->subDays(7))) {
            return 'Offline';
        }

        return 'Visto '.$this->last_activity_at->diffForHumans();
    }

    /**
     * Retorna a classe CSS para o status.
     */
    public function getStatusColorClass(): string
    {
        if ($this->isOnline()) {
            return 'text-green-600 dark:text-green-400';
        }

        if (! $this->last_login_at) {
            return 'text-gray-400 dark:text-gray-600';
        }

        return 'text-gray-500 dark:text-gray-400';
    }

    /**
     * Relacionamento com roles.
     */
    /**
     * @return BelongsToMany<Role, $this, Pivot, 'pivot'>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Verifica se o usuário tem uma permissão específica
     * Admin tem acesso a tudo.
     */
    public function hasPermissionTo(string $permissionName): bool
    {
        // Admin tem acesso a tudo
        if ($this->hasRole('admin')) {
            return true;
        }

        // ACL contextual por setor para módulos de procedimentos/setores
        if ($this->hasSectorScopedPermission($permissionName)) {
            return true;
        }

        // Verifica se alguma role do usuário tem a permissão
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permissionName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se o usuário tem uma role específica.
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Verifica se o usuário é Admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * @return BelongsToMany<Sector, $this, Pivot, 'pivot'>
     */
    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class, 'sector_user')
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Retorna o papel do usuário dentro de um setor específico.
     */
    public function sectorRole(int $sectorId): ?string
    {
        $sector = $this->sectors()
            ->where('sectors.id', $sectorId)
            ->first();

        return $sector?->pivot?->role;
    }

    /**
     * Verifica se pode ao menos visualizar setor.
     */
    public function canAccessSector(int $sectorId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->sectors()->where('sectors.id', $sectorId)->exists();
    }

    /**
     * Verifica se pode gerenciar setor (aprovar/publicar/excluir).
     */
    public function canManageSector(int $sectorId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->sectors()
            ->where('sectors.id', $sectorId)
            ->wherePivot('role', 'manager')
            ->exists();
    }

    /**
     * Verifica se pode editar conteúdo do setor.
     */
    public function canEditSector(int $sectorId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->sectors()
            ->where('sectors.id', $sectorId)
            ->whereIn('sector_user.role', ['manager', 'editor'])
            ->exists();
    }

    /**
     * Verifica permissões contextuais para módulos de Setores/Procedimentos.
     */
    private function hasSectorScopedPermission(string $permissionName): bool
    {
        $readerPermissions = [
            'admin.procedures.index',
            'admin.procedures.show',
            'admin.procedures.compare',
            'admin.procedures.images.show',
        ];

        $editorPermissions = [
            'admin.procedures.create',
            'admin.procedures.store',
            'admin.procedures.edit',
            'admin.procedures.update',
            'admin.procedures.submit-review',
            'admin.procedures.images.upload',
            'admin.procedures.preview',
        ];

        $managerPermissions = [
            'admin.sectors.index',
            'admin.sectors.show',
            'admin.procedures.destroy',
            'admin.procedures.approve',
            'admin.procedures.reject',
            'admin.procedures.publish',
            'admin.procedures.versions.restore',
        ];

        if (in_array($permissionName, $readerPermissions, true)) {
            return $this->sectors()->exists();
        }

        if (in_array($permissionName, $editorPermissions, true)) {
            return $this->sectors()
                ->whereIn('sector_user.role', ['manager', 'editor'])
                ->exists();
        }

        if (in_array($permissionName, $managerPermissions, true)) {
            return $this->sectors()
                ->wherePivot('role', 'manager')
                ->exists();
        }

        return false;
    }
}
