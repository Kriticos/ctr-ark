# 🔒 Sistema de Controle de Acesso (ACL)

## Índice

- [Visão Geral](#visão-geral)
- [Arquitetura do Sistema](#arquitetura-do-sistema)
- [Estrutura de Banco de Dados](#estrutura-de-banco-de-dados)
- [Models e Relacionamentos](#models-e-relacionamentos)
- [Middleware CheckPermission](#middleware-checkpermission)
- [Gates e Policies](#gates-e-policies)
- [Diretivas Blade](#diretivas-blade)
- [Exemplos de Uso](#exemplos-de-uso)
- [Boas Práticas](#boas-práticas)
- [Troubleshooting](#troubleshooting)

---

## Visão Geral

O sistema de Controle de Acesso (ACL - Access Control List) do LaraSaaS é baseado em três pilares fundamentais:

1. **Modules (Módulos)** - Organizadores lógicos de funcionalidades
2. **Permissions (Permissões)** - Baseadas nos nomes das rotas do Laravel
3. **Roles (Papéis)** - Agrupadores de permissões atribuídos aos usuários

### Características Principais

✅ **Permissões baseadas em rotas** - Cada permissão corresponde a uma rota nomeada do Laravel  
✅ **Hierarquia de módulos** - Permissões organizadas logicamente por funcionalidade  
✅ **Role de Admin privilegiada** - Admin tem acesso total automaticamente  
✅ **Middleware automático** - Proteção de rotas transparente  
✅ **Diretivas Blade** - Controle de visibilidade na UI  
✅ **Gates customizados** - Verificação programática de permissões  

---

## Arquitetura do Sistema

```
┌─────────────────────────────────────────────────────┐
│                      USUÁRIO                        │
│              (User Model)                           │
└────────────────────┬────────────────────────────────┘
                     │ N:M (role_user)
                     ▼
┌─────────────────────────────────────────────────────┐
│                      ROLES                          │
│              (Role Model)                           │
│  - Admin (acesso total)                             │
│  - Editor (permissões específicas)                  │
│  - Guest (permissões limitadas)                     │
└────────────────────┬────────────────────────────────┘
                     │ N:M (permission_role)
                     ▼
┌─────────────────────────────────────────────────────┐
│                   PERMISSIONS                       │
│              (Permission Model)                     │
│  - name: nome da rota (ex: admin.users.create)      │
│  - description: descrição legível                   │
└────────────────────┬────────────────────────────────┘
                     │ N:1
                     ▼
┌─────────────────────────────────────────────────────┐
│                    MODULES                          │
│              (Module Model)                         │
│  - name: Usuários, Roles, etc                       │
│  - slug: users, roles, etc                          │
│  - icon: ícone FontAwesome                          │
└─────────────────────────────────────────────────────┘
```

### Fluxo de Autorização

```
1. Requisição HTTP → Rota nomeada
                       ↓
2. Middleware CheckPermission → Obtém nome da rota
                       ↓
3. Verifica usuário autenticado → Auth::user()
                       ↓
4. Checa se é Admin → Acesso TOTAL (bypass)
                       ↓
5. Itera roles do usuário → $user->roles
                       ↓
6. Verifica permissões → $role->permissions
                       ↓
7. Compara nome da rota → permission.name === route.name
                       ↓
8. PERMITE ou NEGA (403)
```

---

## Estrutura de Banco de Dados

### Tabelas Principais

#### `users` (Existente)
```sql
- id: bigint (PK)
- name: string
- email: string (unique)
- password: string (hash)
- avatar: string (nullable)
- last_activity_at: timestamp
- last_login_at: timestamp
- is_online: boolean
- created_at: timestamp
- updated_at: timestamp
```

#### `modules`
```sql
- id: bigint (PK)
- name: string (ex: "Usuários", "Relatórios")
- slug: string (ex: "users", "reports")
- icon: string (ex: "fa-users", "fa-chart-bar")
- description: text (nullable)
- order: integer (para ordenação no menu)
- created_at: timestamp
- updated_at: timestamp
```

#### `permissions`
```sql
- id: bigint (PK)
- module_id: bigint (FK → modules.id)
- name: string (nome da rota: "admin.users.create")
- description: text (ex: "Criar novos usuários")
- created_at: timestamp
- updated_at: timestamp
```

#### `roles`
```sql
- id: bigint (PK)
- name: string (ex: "Administrador", "Editor")
- slug: string (ex: "admin", "editor")
- description: text (nullable)
- created_at: timestamp
- updated_at: timestamp
```

### Tabelas Pivot

#### `role_user` (N:M entre Users e Roles)
```sql
- id: bigint (PK)
- user_id: bigint (FK → users.id)
- role_id: bigint (FK → roles.id)
- created_at: timestamp
- updated_at: timestamp
```

#### `permission_role` (N:M entre Permissions e Roles)
```sql
- id: bigint (PK)
- permission_id: bigint (FK → permissions.id)
- role_id: bigint (FK → roles.id)
- created_at: timestamp
- updated_at: timestamp
```

---

## Models e Relacionamentos

### User Model

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    // Relacionamento N:M com Roles
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Verifica se o usuário tem uma permissão específica
     * Admin tem acesso total (bypass)
     * 
     * @param string $permissionName Nome da rota (ex: admin.users.create)
     * @return bool
     */
    public function hasPermissionTo(string $permissionName): bool
    {
        // Admin tem acesso total
        if ($this->hasRole('admin')) {
            return true;
        }

        // Itera pelas roles do usuário
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permissionName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se o usuário tem uma role específica
     * 
     * @param string $roleSlug Slug da role (ex: admin, editor)
     * @return bool
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Verifica se o usuário é Admin
     * 
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
}
```

### Role Model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    // Relacionamento N:M com Users
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withTimestamps();
    }

    // Relacionamento N:M com Permissions
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role')
            ->withTimestamps();
    }

    /**
     * Verifica se a role tem uma permissão específica
     * 
     * @param string $permissionName Nome da rota
     * @return bool
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()->where('name', $permissionName)->exists();
    }

    /**
     * Verifica se é a role de Admin
     * 
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->slug === 'admin';
    }
}
```

### Permission Model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['name', 'description', 'module_id'];

    // Relacionamento N:1 com Module
    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    // Relacionamento N:M com Roles
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role')
            ->withTimestamps();
    }
}
```

### Module Model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = ['name', 'slug', 'icon', 'description', 'order'];

    // Relacionamento 1:N com Permissions
    public function permissions()
    {
        return $this->hasMany(Permission::class)
            ->orderBy('name');
    }
}
```

---

## Middleware CheckPermission

### Localização
`app/Http/Middleware/CheckPermission.php`

### Código Completo

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Se não estiver autenticado, redireciona para login
        if (!$user) {
            return redirect()->route('login');
        }

        // Obtém o nome da rota atual
        $routeName = Route::currentRouteName();

        // Se não houver nome de rota ou for admin, permite acesso
        if (!$routeName || $user->isAdmin()) {
            return $next($request);
        }

        // Verifica se o usuário tem permissão
        if (!$user->hasPermissionTo($routeName)) {
            abort(403, 'Você não tem permissão para acessar este recurso.');
        }

        return $next($request);
    }
}
```

### Funcionamento Detalhado

1. **Autenticação**: Verifica se há usuário autenticado, caso contrário redireciona para login
2. **Nome da Rota**: Obtém o nome da rota atual usando `Route::currentRouteName()`
3. **Bypass Admin**: Se for admin, permite acesso imediatamente (bypass completo)
4. **Verificação**: Chama `$user->hasPermissionTo($routeName)` para verificar permissão
5. **Resultado**: Permite acesso (200) ou nega com erro 403

### Registro do Middleware

O middleware é registrado em `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'check.permission' => \App\Http\Middleware\CheckPermission::class,
    ]);
})
```

### Aplicação nas Rotas

Em `routes/web.php`:

```php
// Grupo protegido pelo middleware
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'check.permission'])
    ->group(function () {
    
    // Dashboard não precisa de permissão
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard')->withoutMiddleware('check.permission');
    
    // Rotas protegidas por permissão
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class);
    Route::resource('modules', ModuleController::class);
});
```

**Importante**: Use `withoutMiddleware('check.permission')` para rotas que não precisam de verificação específica (como dashboard ou perfil).

---

## Gates e Policies

### Gate: access-route

Definido em `app/Providers/AppServiceProvider.php`:

```php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    // Gate para verificar acesso a rotas
    Gate::define('access-route', function ($user, $routeName) {
        return $user->hasPermissionTo($routeName);
    });
}
```

### Uso Programático

```php
// Verificar se pode acessar uma rota
if (Gate::allows('access-route', 'admin.users.create')) {
    // Usuário tem permissão
}

// Ou negando acesso
if (Gate::denies('access-route', 'admin.users.create')) {
    // Usuário NÃO tem permissão
}

// Lançar exceção se não tiver permissão
Gate::authorize('access-route', 'admin.users.create');
```

### Uso em Controllers

```php
namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function create()
    {
        // Verifica permissão programaticamente
        if (Gate::denies('access-route', 'admin.users.create')) {
            abort(403, 'Sem permissão para criar usuários');
        }
        
        return view('admin.users.create');
    }
    
    public function store(Request $request)
    {
        // Lança exceção 403 se não tiver permissão
        Gate::authorize('access-route', 'admin.users.store');
        
        // Continua com a lógica...
    }
}
```

---

## Diretivas Blade

### @can - Verificar Permissão Única

Mostra conteúdo apenas se o usuário tiver a permissão especificada.

```blade
@can('access-route', 'admin.users.create')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        Novo Usuário
    </a>
@endcan
```

**Exemplo Real (Botão de Criar)**:
```blade
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Usuários</h1>
    
    @can('access-route', 'admin.users.create')
        <a href="{{ route('admin.users.create') }}" 
           class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
            <i class="fas fa-plus mr-2"></i> Novo Usuário
        </a>
    @endcan
</div>
```

### @cannot - Verificar Falta de Permissão

Mostra conteúdo apenas se o usuário NÃO tiver a permissão.

```blade
@cannot('access-route', 'admin.users.create')
    <p class="text-gray-500">Você não tem permissão para criar usuários.</p>
@endcannot
```

### @canany - Verificar Múltiplas Permissões (OU)

Mostra conteúdo se o usuário tiver QUALQUER UMA das permissões listadas.

```blade
@canany(['access-route', 'access-route'], ['admin.users.edit', 'admin.users.show'])
    <div class="actions">
        @can('access-route', 'admin.users.show')
            <a href="{{ route('admin.users.show', $user) }}">Ver</a>
        @endcan
        
        @can('access-route', 'admin.users.edit')
            <a href="{{ route('admin.users.edit', $user) }}">Editar</a>
        @endcan
    </div>
@endcanany
```

**Exemplo Real (Menu Sidebar)**:
```blade
<!-- Mostra item do menu se tiver acesso a listagem OU criação -->
@canany(['access-route', 'access-route'], ['admin.users.index', 'admin.users.create'])
    <li class="menu-item">
        <a href="{{ route('admin.users.index') }}" class="flex items-center px-4 py-2">
            <i class="fas fa-users mr-3"></i>
            <span>Usuários</span>
        </a>
    </li>
@endcanany
```

### @else e @elsecan

Você pode usar `@else` com as diretivas `@can`:

```blade
@can('access-route', 'admin.users.edit')
    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
@else
    <p class="text-red-500">Você não pode editar este usuário.</p>
@endcan
```

Ou usar `@elsecan` para verificar outra permissão:

```blade
@can('access-route', 'admin.users.destroy')
    <button class="btn btn-danger">Excluir</button>
@elsecan('access-route', 'admin.users.edit')
    <button class="btn btn-warning">Editar</button>
@else
    <span class="text-gray-500">Sem ações disponíveis</span>
@endcan
```

### Exemplo Completo: Tabela com Ações

```blade
<table class="min-w-full">
    <thead>
        <tr>
            <th>Nome</th>
            <th>Email</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        @foreach($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td class="flex gap-2">
                    {{-- Botão Visualizar --}}
                    @can('access-route', 'admin.users.show')
                        <a href="{{ route('admin.users.show', $user) }}" 
                           class="text-blue-600 hover:text-blue-800"
                           title="Visualizar">
                            <i class="fas fa-eye"></i>
                        </a>
                    @endcan
                    
                    {{-- Botão Editar --}}
                    @can('access-route', 'admin.users.edit')
                        <a href="{{ route('admin.users.edit', $user) }}" 
                           class="text-yellow-600 hover:text-yellow-800"
                           title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                    @endcan
                    
                    {{-- Botão Excluir --}}
                    @can('access-route', 'admin.users.destroy')
                        <form action="{{ route('admin.users.destroy', $user) }}" 
                              method="POST" 
                              class="inline delete-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="text-red-600 hover:text-red-800"
                                    title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    @endcan
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
```

---

## Exemplos de Uso

### 1. Proteger Grupo de Rotas

```php
// routes/web.php
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'check.permission'])
    ->group(function () {
        Route::resource('users', UserController::class);
    });
```

Isso protege TODAS as rotas do resource:
- `admin.users.index` → GET /admin/users
- `admin.users.create` → GET /admin/users/create
- `admin.users.store` → POST /admin/users
- `admin.users.show` → GET /admin/users/{user}
- `admin.users.edit` → GET /admin/users/{user}/edit
- `admin.users.update` → PUT/PATCH /admin/users/{user}
- `admin.users.destroy` → DELETE /admin/users/{user}

### 2. Criar Permissões para um CRUD

```php
// database/seeders/PermissionSeeder.php
$module = Module::where('slug', 'users')->first();

$permissions = [
    ['name' => 'admin.users.index', 'description' => 'Listar usuários'],
    ['name' => 'admin.users.create', 'description' => 'Criar usuário'],
    ['name' => 'admin.users.store', 'description' => 'Salvar usuário'],
    ['name' => 'admin.users.show', 'description' => 'Visualizar usuário'],
    ['name' => 'admin.users.edit', 'description' => 'Editar usuário'],
    ['name' => 'admin.users.update', 'description' => 'Atualizar usuário'],
    ['name' => 'admin.users.destroy', 'description' => 'Excluir usuário'],
];

foreach ($permissions as $perm) {
    Permission::create([
        'module_id' => $module->id,
        'name' => $perm['name'],
        'description' => $perm['description'],
    ]);
}
```

### 3. Atribuir Permissões a uma Role

```php
// database/seeders/RoleSeeder.php
$editorRole = Role::create([
    'name' => 'Editor',
    'slug' => 'editor',
    'description' => 'Pode editar conteúdo mas não excluir',
]);

// Buscar permissões
$permissions = Permission::whereIn('name', [
    'admin.users.index',
    'admin.users.show',
    'admin.users.edit',
    'admin.users.update',
])->pluck('id');

// Atribuir permissões à role
$editorRole->permissions()->attach($permissions);
```

### 4. Atribuir Role a um Usuário

```php
$user = User::find(1);
$role = Role::where('slug', 'editor')->first();

// Atribuir role
$user->roles()->attach($role->id);

// Ou atribuir múltiplas roles
$user->roles()->attach([1, 2, 3]);

// Sincronizar (remove antigas e adiciona novas)
$user->roles()->sync([2]);
```

### 5. Ocultar Menu Lateral Baseado em Permissão

```blade
<!-- resources/views/layouts/admin.blade.php -->
<nav class="sidebar">
    <!-- Item sempre visível -->
    <a href="{{ route('admin.dashboard') }}">
        <i class="fas fa-home"></i> Dashboard
    </a>
    
    <!-- Item condicional -->
    @can('access-route', 'admin.users.index')
        <a href="{{ route('admin.users.index') }}">
            <i class="fas fa-users"></i> Usuários
        </a>
    @endcan
    
    <!-- Submenu condicional -->
    @canany(['access-route', 'access-route', 'access-route'], 
            ['admin.modules.index', 'admin.roles.index', 'admin.permissions.index'])
        <div class="submenu">
            <span><i class="fas fa-shield-alt"></i> Controle de Acesso</span>
            
            @can('access-route', 'admin.modules.index')
                <a href="{{ route('admin.modules.index') }}">Módulos</a>
            @endcan
            
            @can('access-route', 'admin.roles.index')
                <a href="{{ route('admin.roles.index') }}">Roles</a>
            @endcan
            
            @can('access-route', 'admin.permissions.index')
                <a href="{{ route('admin.permissions.index') }}">Permissões</a>
            @endcan
        </div>
    @endcanany
</nav>
```

### 6. Verificação em Controller

```php
namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function exportPdf()
    {
        // Verifica permissão antes de processar
        if (Gate::denies('access-route', 'admin.reports.export')) {
            return back()->with('error', 'Você não tem permissão para exportar relatórios.');
        }
        
        // Lógica de exportação...
    }
    
    public function financialReport()
    {
        // Lança exceção 403 se não tiver permissão
        Gate::authorize('access-route', 'admin.reports.financial');
        
        // Busca dados financeiros sensíveis...
    }
}
```

### 7. Verificação em API

```php
namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

class UserApiController extends Controller
{
    public function destroy(User $user): JsonResponse
    {
        if (!auth()->user()->hasPermissionTo('admin.users.destroy')) {
            return response()->json([
                'message' => 'Sem permissão para excluir usuários'
            ], 403);
        }
        
        $user->delete();
        
        return response()->json([
            'message' => 'Usuário excluído com sucesso'
        ], 200);
    }
}
```

---

## Boas Práticas

### 1. Nomenclatura de Permissões

✅ **CORRETO**: Usar o nome exato da rota
```php
// Rota
Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');

// Permissão
Permission::create(['name' => 'admin.users.index', ...]);
```

❌ **ERRADO**: Usar nome diferente
```php
// NÃO FAÇA ISSO
Permission::create(['name' => 'listar-usuarios', ...]); // Nome diferente da rota!
```

### 2. Role de Admin

Sempre deixe o Admin com acesso total via método `isAdmin()`:

```php
public function hasPermissionTo(string $permissionName): bool
{
    // SEMPRE verificar admin primeiro
    if ($this->hasRole('admin')) {
        return true;
    }
    
    // Depois verifica permissões específicas
    foreach ($this->roles as $role) {
        if ($role->hasPermission($permissionName)) {
            return true;
        }
    }
    
    return false;
}
```

### 3. Organização de Módulos

Agrupe permissões logicamente:

```
Módulo: Usuários
├── admin.users.index
├── admin.users.create
├── admin.users.store
├── admin.users.show
├── admin.users.edit
├── admin.users.update
└── admin.users.destroy

Módulo: Relatórios
├── admin.reports.index
├── admin.reports.financial
├── admin.reports.export
└── admin.reports.send-email
```

### 4. Testes Automatizados

Sempre teste suas permissões:

```php
// tests/Feature/PermissionTest.php
public function test_user_without_permission_cannot_access_route()
{
    $user = User::factory()->create();
    
    $this->actingAs($user)
        ->get(route('admin.users.create'))
        ->assertForbidden(); // 403
}

public function test_admin_can_access_all_routes()
{
    $admin = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $admin->roles()->attach($adminRole);
    
    $this->actingAs($admin)
        ->get(route('admin.users.create'))
        ->assertOk(); // 200
}

public function test_user_with_permission_can_access_route()
{
    $user = User::factory()->create();
    $role = Role::factory()->create();
    $permission = Permission::factory()->create(['name' => 'admin.users.create']);
    
    $role->permissions()->attach($permission);
    $user->roles()->attach($role);
    
    $this->actingAs($user)
        ->get(route('admin.users.create'))
        ->assertOk(); // 200
}
```

### 5. Eager Loading

Evite N+1 queries ao carregar permissões:

```php
// ❌ ERRADO: N+1 query
$users = User::all();
foreach ($users as $user) {
    if ($user->hasPermissionTo('admin.users.edit')) {
        // ...
    }
}

// ✅ CORRETO: Eager loading
$users = User::with(['roles.permissions'])->get();
foreach ($users as $user) {
    if ($user->hasPermissionTo('admin.users.edit')) {
        // ...
    }
}
```

### 6. Cache de Permissões

Para sistemas grandes, considere cachear permissões:

```php
public function hasPermissionTo(string $permissionName): bool
{
    if ($this->hasRole('admin')) {
        return true;
    }
    
    // Cache por 1 hora
    $cacheKey = "user.{$this->id}.permission.{$permissionName}";
    
    return Cache::remember($cacheKey, 3600, function () use ($permissionName) {
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permissionName)) {
                return true;
            }
        }
        return false;
    });
}
```

Lembre-se de limpar o cache ao atualizar permissões:

```php
// Ao atualizar permissões de uma role
$role->permissions()->sync($permissionIds);

// Limpar cache de todos os usuários com essa role
foreach ($role->users as $user) {
    Cache::forget("user.{$user->id}.permission.*");
}
```

---

## Troubleshooting

### Problema: Sempre retorna 403

**Possíveis causas:**
1. Permissão não cadastrada no banco
2. Nome da permissão diferente do nome da rota
3. Usuário não tem a role atribuída
4. Role não tem a permissão atribuída

**Solução:**
```php
// 1. Verificar se a permissão existe
Permission::where('name', 'admin.users.create')->first(); // Deve retornar algo

// 2. Verificar nome da rota
Route::currentRouteName(); // Deve retornar 'admin.users.create'

// 3. Verificar roles do usuário
$user->roles; // Deve ter pelo menos 1 role

// 4. Verificar permissões da role
$user->roles->first()->permissions; // Deve incluir a permissão
```

### Problema: Admin não tem acesso total

**Causa:** Método `isAdmin()` não está funcionando

**Solução:**
```php
// Verificar se o slug está correto
$role = Role::where('slug', 'admin')->first();
$role->slug; // Deve ser exatamente 'admin' (lowercase)

// Verificar se o usuário tem a role
$user->hasRole('admin'); // Deve retornar true
```

### Problema: Middleware não está executando

**Causa:** Middleware não registrado ou não aplicado

**Solução:**
```php
// 1. Verificar registro em bootstrap/app.php
$middleware->alias([
    'check.permission' => \App\Http\Middleware\CheckPermission::class,
]);

// 2. Verificar aplicação nas rotas
Route::middleware(['auth', 'check.permission'])->group(...);
```

### Problema: Diretiva @can não funciona

**Causa:** Gate não definido

**Solução:**
```php
// Verificar em app/Providers/AppServiceProvider.php
Gate::define('access-route', function ($user, $routeName) {
    return $user->hasPermissionTo($routeName);
});
```

### Problema: Permissões não aparecem na interface

**Causa:** Seeders não executados

**Solução:**
```bash
# Executar seeders
./vendor/bin/sail artisan db:seed --class=ModuleSeeder
./vendor/bin/sail artisan db:seed --class=PermissionSeeder
./vendor/bin/sail artisan db:seed --class=RoleSeeder

# Ou todos de uma vez
./vendor/bin/sail artisan db:seed
```

---

## Conclusão

O sistema ACL do LaraSaaS oferece controle granular de acesso baseado em:

- ✅ **Permissões vinculadas a rotas** - Fácil manutenção
- ✅ **Organização por módulos** - Estrutura lógica
- ✅ **Middleware automático** - Proteção transparente
- ✅ **Diretivas Blade** - Controle de UI
- ✅ **Role Admin privilegiada** - Acesso total
- ✅ **Extensível** - Fácil adicionar novas permissões

Para mais informações, consulte:
- [Documentação Laravel - Authorization](https://laravel.com/docs/authorization)
- [README do Projeto](../README.md)
- [Scheduler Documentation](./SCHEDULER.md)
