# 🔒 Módulo de Controle de Acesso (ACL) no Laravel

Este projeto precisa de um Módulo de Controle de Acesso baseado em Roles (Papéis) e Permissions (Permissões). As permissões devem ser baseadas nos nomes das rotas do Laravel.

## 1. Modelos (Models) e Estrutura de Banco de Dados

Crie os seguintes modelos e defina seus relacionamentos:

### Models
1.  **`Role`**: Para armazenar os papéis (ex: Admin, Editor, Guest).
2.  **`Permission`**: Para armazenar as permissões, onde o campo `name` será o **nome da rota** (ex: `users.create`, `products.edit`).
3.  **`User` (Existente)**: Adicione o relacionamento com `Role`.

### Relacionamentos e Tabelas Pivot
* **`User` (N:M) `Role`**: Tabela pivot `role_user`.
* **`Role` (N:M) `Permission`**: Tabela pivot `permission_role`.

### Métodos Auxiliares
Adicione um método **`hasPermissionTo($permissionName)`** ao modelo `User` que checa se o usuário, através de suas roles, possui a permissão com o nome fornecido.

## 2. Middleware de Checagem de Acesso

Crie o middleware **`CheckPermission`**:

* **Objetivo:** Proteger rotas.
* **Lógica:**
    1.  Obter o nome da rota atual usando `Route::currentRouteName()`.
    2.  Obter o usuário autenticado (`Auth::user()`).
    3.  Usar o método `$user->hasPermissionTo($routeName)` para verificar o acesso.
    4.  Permitir ou negar acesso (abortar com erro 403).

Registre este middleware como `check.permission` no `app/Http/Kernel.php`.

## 3. Diretiva de View (Blade) e Gate

### Gate de Permissão
* Defina um Gate no `AuthServiceProvider` chamado **`access-route`**.
* Este Gate deve aceitar o `$user` e o `$permissionName` como argumentos.
* Ele deve retornar `$user->hasPermissionTo($permissionName)`.

### Uso na View (Menus)
* A visibilidade dos itens de menu deve ser controlada usando a diretiva **`@can('access-route', 'nome.da.rota')`**.

## 4. Aplicação e Exemplos

* Mostre um exemplo de como aplicar o middleware **`check.permission`** em um grupo de rotas no `routes/web.php`.
* Assegure-se de que todas as rotas protegidas tenham um **nome definido** (ex: `.name('resource.action')`).

## 5. Criação de Interfaces de Administração
* Crie uma interface administrativa para gerenciar Roles e Permissions.
* Permita atribuir/remover Roles para Usuários e Permissões para Roles.
* Crie a interface usando Blade e controllers do Laravel. por exemplo, `RoleController` e `PermissionController`.
* Utilize o padrão de interfaces já criados no módulo de usuários.
* Utilize como exemplo as views de usuários já existentes no projeto: resources/views/admin/users
* Assegure-se de que apenas usuários com a permissão adequada possam acessar essas interfaces administrativas.
* utilize form requests para validação dos dados. E garanta que os form requests verifiquem se o usuário autenticado.

## 6. Testes
* Escreva testes unitários para o método `hasPermissionTo`.
* Escreva testes de integração para o middleware `CheckPermission`.

## 7. Estratégia de Testes e Cobertura de Código

### 7.1 Requisitos Gerais de Testes

Todos os arquivos PHP criados para este projeto **DEVEM** possuir testes correspondentes com os seguintes requisitos:

- **Cobertura Mínima**: 90% de cobertura de código no projeto inteiro
- **Tipos de Teste**: Feature tests (integração) e Unit tests (unidade)
- **Framework**: Pest PHP com Livewire Assertions (quando aplicável)
- **Comando para Validação**: `sail test --coverage --min=90`

### 7.2 Padrões e Boas Práticas

#### PSR Compliance
- Seguir **PSR-12** para estilos de código
- Seguir **PSR-4** para autoloading
- Usar type hints em todos os métodos e propriedades (PHP 8.0+)
- Usar named arguments quando aplicável

#### Estrutura de Testes

```
tests/
├── Feature/          # Testes de Integração (Feature Tests)
│   ├── Livewire/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   └── Requests/
│   └── Models/
└── Unit/            # Testes Unitários
    ├── Models/
    ├── Services/
    ├── Actions/
    └── Helpers/
```

### 7.3 Testes de Models

**Obrigação**: Toda nova Model **DEVE** ter:
1. ✅ Uma Factory correspondente em `database/factories/`
2. ✅ Testes Feature em `tests/Feature/Models/`
3. ✅ Mínimo 100% de cobertura para a Model

**Exemplo de Factory**:
```php
<?php

namespace Database\Factories;

use App\Models\YourModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class YourModelFactory extends Factory
{
    protected $model = YourModel::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            // ... outros campos
        ];
    }
    
    // Estados para diferentes cenários
    public function active(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
```

**Exemplo de Teste de Model**:
```php
<?php

use App\Models\YourModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('model can be created with mass assignment', function () {
    $model = YourModel::factory()->create(['name' => 'Test']);
    expect($model->name)->toBe('Test');
});

test('model has correct relationships', function () {
    $model = YourModel::factory()->create();
    expect($model->relatedModels)->toBeDefined();
});
```

### 7.4 Testes de Controllers

**Requisitos**:
1. ✅ Feature tests para cada ação (index, create, store, edit, update, destroy)
2. ✅ Validar respostas HTTP (status, view/json)
3. ✅ Testar autenticação e autorização
4. ✅ Testar validação de Form Requests

**Exemplo**:
```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('index shows all resources', function () {
    $this->get(route('resources.index'))
        ->assertOk()
        ->assertViewHas('resources');
});

test('store requires authentication', function () {
    $this->actingAs(null)
        ->post(route('resources.store'), [])
        ->assertRedirect(route('login'));
});
```

### 7.5 Testes de Middleware

**Requisitos**:
1. ✅ Testar permissão concedida (sucesso)
2. ✅ Testar permissão negada (403 ou redirecionamento)
3. ✅ Testar usuário não autenticado

**Exemplo**:
```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('check permission middleware allows access with permission', function () {
    $user = User::factory()
        ->hasPermissions('users.index')
        ->create();

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertOk();
});

test('check permission middleware denies access without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertForbidden();
});
```

### 7.6 Testes de Form Requests

**Requisitos**:
1. ✅ Validar regras de validação
2. ✅ Testar dados válidos e inválidos
3. ✅ Testar autorização (se aplicável)

**Exemplo**:
```php
<?php

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('store user request validates required fields', function () {
    $this->post(route('users.store'), [])
        ->assertSessionHasErrors(['name', 'email']);
});

test('store user request accepts valid data', function () {
    $this->post(route('users.store'), [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
    ])
    ->assertRedirect(route('users.index'));
});
```

### 7.7 Testes de Componentes Livewire

**Requisitos**:
1. ✅ Testar mounting e rendering
2. ✅ Testar actions/methods
3. ✅ Testar property binding
4. ✅ Testar event dispatching
5. ✅ Testar validação

**Exemplo**:
```php
<?php

use App\Livewire\Admin\UsersTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('users table component mounts successfully', function () {
    Livewire::test(UsersTable::class)
        ->assertSuccessful();
});

test('users table displays users', function () {
    User::factory(3)->create();

    Livewire::test(UsersTable::class)
        ->assertViewHas('users', fn ($users) => $users->count() === 3);
});

test('users table can search', function () {
    $user = User::factory()->create(['name' => 'John Doe']);

    Livewire::test(UsersTable::class)
        ->set('search', 'John')
        ->assertViewHas('users', fn ($users) => 
            $users->contains('id', $user->id)
        );
});

test('users table dispatches delete event', function () {
    $user = User::factory()->create();

    Livewire::test(UsersTable::class)
        ->call('delete', $user->id)
        ->assertDispatched('user-deleted');
});
```

### 7.8 Testes com APIs Externas (Mocking)

**Requisitos**: Todas as chamadas a APIs externas **DEVEM** ser mockadas nos testes

**Exemplo com HTTP Mock**:
```php
<?php

use Illuminate\Support\Facades\Http;

test('service fetches data from external api', function () {
    Http::fake([
        'https://api.example.com/*' => Http::response(['data' => 'value'], 200)
    ]);

    $result = app(ExternalService::class)->fetch();

    expect($result['data'])->toBe('value');
    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.example.com/endpoint';
    });
});
```

### 7.9 Estrutura de Teste Setup

**beforeEach Hook** - Para testes de Feature:
```php
<?php

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    // Seed dados necessários
    $this->seed(RolePermissionSeeder::class);
    
    // Criar usuário autenticado
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});
```

### 7.10 Verificação de Coverage

**Executar com coverage**:
```bash
# Verificar coverage geral
sail test --coverage --min=90

# Coverage por arquivo específico
sail test --coverage path/to/test

# Gerar relatório HTML (se configurado)
sail test --coverage --coverage-html coverage/
```

### 7.11 Checklist para Nova Feature

Ao criar uma nova feature, seguir este checklist:

- [ ] Model criada com Factory
- [ ] Controller criado com testes Feature
- [ ] Form Request criado com testes
- [ ] Middleware criado (se necessário) com testes
- [ ] Livewire Component criado (se necessário) com testes
- [ ] Testes cobrem 90%+ do código
- [ ] PHPDoc annotations em todos os métodos
- [ ] Type hints em todos os parâmetros
- [ ] Validação de entrada em todos os endpoints
- [ ] Autenticação/Autorização verificada
- [ ] Testes passam: `sail test --coverage --min=90`

# Instruções para o GitHub Copilot
Estas instruções ajudam o GitHub Copilot a entender o contexto do projeto e fornecer sugestões de código mais relevantes. Por favor, siga estas diretrizes ao sugerir código:

1. **Contexto do Projeto**: Este projeto é uma aplicação web construída com Laravel que inclui um sistema de autenticação e um painel administrativo. O foco principal é a implementação de um sistema de Controle de Acesso baseado em Roles e Permissions.

2. **Padrões de Código**: Siga as melhores práticas do Laravel e do PHP:
   - Utilize Eloquent ORM para interações com o banco de dados
   - Siga as convenções de nomenclatura do Laravel
   - Implemente PSR-12 para estilos de código
   - Use type hints em todos os métodos (PHP 8.0+)
   - Adicione PHPDoc para documentação

3. **Segurança**: Priorize a segurança ao sugerir código, especialmente ao lidar com autenticação e autorização:
   - Validar entrada de dados
   - Usar prepared statements (Eloquent faz isso)
   - Verificar autorização antes de executar ações
   - Usar middleware para proteger rotas

4. **Clareza e Manutenção**: Escreva código claro e bem documentado:
   - Inclua comments explicativos quando necessário
   - Use nomes descritivos para variáveis e funções
   - Mantenha métodos pequenos e com responsabilidade única
   - Facilite a manutenção futura

5. **Testes**: SEMPRE sugira a inclusão de testes quando criar código novo:
   - **Para Models**: Criar Factory + Tests Feature (100% cobertura)
   - **Para Controllers**: Criar testes Feature para cada ação
   - **Para Middleware**: Criar testes Feature para autorização
   - **Para Form Requests**: Criar testes de validação
   - **Para Livewire Components**: Criar testes com Livewire::test()
   - **Para APIs Externas**: MOCKAR em testes com Http::fake()
   - **Cobertura Mínima**: 90% no projeto inteiro
   - **Comando**: `sail test --coverage --min=90`

6. **Estrutura de Arquivos**: Mantenha a organização:
   - Controllers em `app/Http/Controllers/`
   - Models em `app/Models/`
   - Factories em `database/factories/`
   - Testes em `tests/Feature/` ou `tests/Unit/`
   - Livewire Components em `app/Livewire/`
   - Views em `resources/views/`

# Fim das Instruções
