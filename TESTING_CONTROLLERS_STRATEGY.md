# 🧪 Estratégia de Testes para Controllers

## Estrutura de Testes Completa

### 1️⃣ **Padrão Geral de Testes para Cada Controller**

```
test_name_structure: {controller}_{method}_{scenario}
Exemplo: test_user_index_with_search_returns_filtered_users
```

### 2️⃣ **Categorias de Testes Obrigatórias**

#### A. **Autenticação & Autorização**
- [ ] Usuário não autenticado → redireciona para login (401)
- [ ] Usuário autenticado sem permissão → retorna 403
- [ ] Usuário com permissão → retorna sucesso (200)
- [ ] Admin sempre tem acesso (se aplicável)

#### B. **Validação de Request**
- [ ] Campos obrigatórios ausentes → 422
- [ ] Validação de formato (email, url, etc) → 422
- [ ] Validação de comprimento → 422
- [ ] Validação de unicidade (email, slug) → 422
- [ ] Dados válidos → sucesso 200/201/302

#### C. **Resposta HTTP**
- [ ] Status code correto
- [ ] View correto renderizado (para GET)
- [ ] JSON correto (para API)
- [ ] Redirecionamento para rota correta

#### D. **Efeitos Colaterais (Side Effects)**
- [ ] Registro criado/atualizado/deletado no banco
- [ ] Avatar upload/delete funciona
- [ ] Roles sincronizadas
- [ ] Logs registrados

#### E. **Mensagens de Sucesso/Erro**
- [ ] Flash message de sucesso
- [ ] Flash message de erro
- [ ] Mensagens são visíveis na sessão

#### F. **Casos de Anomalia (Edge Cases)**
- [ ] Usuário tenta deletar a si mesmo
- [ ] Arquivo muito grande no upload
- [ ] Extensão de arquivo inválida
- [ ] Banco de dados indisponível
- [ ] Conflito de dados duplicados
- [ ] ID inexistente

### 3️⃣ **Estrutura de Teste com beforeEach**

```php
<?php

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed de dados necessários
    $this->seed(RolePermissionSeeder::class);
    
    // Criar usuários com diferentes papéis
    $this->admin = User::factory()
        ->create()
        ->assignRole('admin');
    
    $this->editor = User::factory()
        ->create()
        ->assignRole('editor');
    
    $this->guest = User::factory()->create();
    
    // Usuário padrão para testes
    $this->user = $this->admin;
});

test('index requires authentication', function () {
    $this->get(route('admin.users.index'))
        ->assertRedirect(route('login'));
});
```

### 4️⃣ **Mapeamento de Testes por Controller**

#### **UserController** (7 métodos)
- [ ] index (com search) → 4 testes
- [ ] create → 2 testes
- [ ] store → 5 testes
- [ ] show → 3 testes
- [ ] edit → 2 testes
- [ ] update → 5 testes
- [ ] destroy → 4 testes
- [ ] deleteAvatar → 3 testes
- **Subtotal**: ~28 testes

#### **RoleController** (7 métodos)
- [ ] index → 3 testes
- [ ] create → 2 testes
- [ ] store → 4 testes
- [ ] edit → 2 testes
- [ ] update → 4 testes
- [ ] destroy → 3 testes
- **Subtotal**: ~18 testes

#### **PermissionController** (similar)
- **Subtotal**: ~18 testes

#### **MenuController** (similar)
- **Subtotal**: ~18 testes

#### **ModuleController** (similar)
- **Subtotal**: ~18 testes

#### **ProfileController** (simpler, 2-3 métodos)
- **Subtotal**: ~6 testes

### 5️⃣ **Checklist de Anomalias Obrigatórias**

#### **Upload/Storage Anomalies**
- [ ] Arquivo > tamanho máximo
- [ ] Extensão não permitida
- [ ] Arquivo corrompido
- [ ] Espaço em disco insuficiente
- [ ] Arquivo deletado antes do processamento

#### **Database Anomalies**
- [ ] Tentativa de deletar registro inexistente
- [ ] Tentativa de atualizar registro inexistente
- [ ] Violação de constraint (unique, foreign key)
- [ ] Transação falha meio do processo
- [ ] Deadlock em operações concorrentes

#### **Business Logic Anomalies**
- [ ] Usuário tenta deletar a si mesmo
- [ ] Tentativa de editar outro usuário (sem permissão)
- [ ] Tentativa de remover última role do admin
- [ ] Tentar ativar usuário já ativo
- [ ] Sincronizar roles que não existem

#### **Input Validation Anomalies**
- [ ] Injeção SQL em search
- [ ] XSS em campos de texto
- [ ] Email válido mas não verificado
- [ ] Senha muito fraca
- [ ] Nome com caracteres especiais/unicode

#### **Concurrency Anomalies**
- [ ] Duas requisições atualizando mesmo recurso
- [ ] Delete enquanto está sendo editado
- [ ] Race condition em avatar upload

### 6️⃣ **Exemplo de Teste Completo**

```php
test('user can create user with avatar and roles', function () {
    $this->actingAs($this->admin);
    
    $userData = [
        'name' => 'João Silva',
        'email' => 'joao@example.com',
        'password' => 'SecurePassword123!',
        'password_confirmation' => 'SecurePassword123!',
        'roles' => [1, 2], // IDs de roles
        'avatar' => UploadedFile::fake()->image('avatar.jpg', 100, 100),
    ];
    
    $response = $this->post(route('admin.users.store'), $userData);
    
    // Verificar redirecionamento
    $response->assertRedirect(route('admin.users.index'));
    
    // Verificar mensagem de sucesso
    $response->assertSessionHas('success', 'Usuário criado com sucesso!');
    
    // Verificar registro criado
    $this->assertDatabaseHas('users', [
        'email' => 'joao@example.com',
        'name' => 'João Silva',
    ]);
    
    // Verificar roles sincronizadas
    $user = User::where('email', 'joao@example.com')->first();
    expect($user->roles->count())->toBe(2);
    
    // Verificar avatar existe
    expect($user->avatar)->not->toBeNull();
    Storage::disk('public')->assertExists($user->avatar);
});
```

### 7️⃣ **Logs e Eventos**

#### Testar que Logs são Registrados
```php
test('user creation is logged', function () {
    Log::shouldReceive('info')
        ->with(containing('Usuário criado'))
        ->once();
    
    // ... realizar ação ...
});
```

#### Testar que Eventos são Disparados
```php
test('user creation dispatches event', function () {
    Event::fake();
    
    // ... realizar ação ...
    
    Event::assertDispatched(UserCreated::class);
});
```

### 8️⃣ **Validação de Responses**

```php
// HTML Response
->assertView('admin.users.index')
->assertViewHas('users')
->assertViewHas('search')

// JSON Response
->assertJsonStructure([
    'data' => [
        'id',
        'name',
        'email',
    ]
])

// Redirect
->assertRedirect(route('admin.users.index'))
->assertSessionHas('success')
->assertSessionHasErrors('email')
```

---

## 📊 Total de Testes Planejados

- **UserController**: ~28 testes
- **RoleController**: ~18 testes
- **PermissionController**: ~18 testes
- **MenuController**: ~18 testes
- **ModuleController**: ~18 testes
- **ProfileController**: ~6 testes
- **Anomaly Tests**: ~20 testes (compartilhados)

**Total**: ~126 novos testes para Controllers

**Impacto**: +40-50% na cobertura geral
