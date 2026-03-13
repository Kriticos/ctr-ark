# 📋 Plano de Testes para Cobertura 90%

## Status Atual
- **Cobertura Global**: 25.3%
- **Testes Totais**: 162
- **Models com 100% cobertura**: User, Role, Permission, Module, Menu ✅
- **Componentes Livewire com cobertura**: MenusTable (100%), ModulesTable (87.9%)

## 🎯 Próximas Prioridades para Atingir 90%

### 1️⃣ **Testes de Controllers** (Impacto Alto)
Atualmente: **~5-16% de cobertura**

Controllers a testar:
- [ ] `app/Http/Controllers/Admin/UserController` (7.8%)
- [ ] `app/Http/Controllers/Admin/RoleController` (12.5%)
- [ ] `app/Http/Controllers/Admin/PermissionController` (16.1%)
- [ ] `app/Http/Controllers/Admin/MenuController` (5.3%)
- [ ] `app/Http/Controllers/Admin/ModuleController` (0%)
- [ ] `app/Http/Controllers/Admin/ProfileController` (0%)
- [ ] `app/Http/Controllers/Auth/*` (0%)

**Cada Controller precisa de 5-7 testes Feature:**
- index (listagem)
- create (formulário)
- store (salvar)
- edit (editar)
- update (atualizar)
- destroy (deletar)

Estimado: **~12 Controllers × 6 testes = ~72 novos testes**

### 2️⃣ **Testes de Middleware** (Impacto Médio)
Atualmente: **55.6% de cobertura (CheckPermission)**

Middleware a testar:
- [ ] `CheckPermission` - completar para 100%
- [ ] `UpdateLastActivity` - já em 100% ✅

Estimado: **~5-8 novos testes**

### 3️⃣ **Testes de Form Requests** (Impacto Médio)
Atualmente: **0% de cobertura**

Form Requests a testar:
- [ ] `StoreUserRequest`
- [ ] `UpdateUserRequest`
- [ ] `StoreRoleRequest`
- [ ] `UpdateRoleRequest`
- [ ] `StorePermissionRequest`
- [ ] `UpdatePermissionRequest`
- [ ] `StoreMenuRequest`
- [ ] `UpdateMenuRequest`
- [ ] `StoreModuleRequest`
- [ ] `UpdateModuleRequest`

**Cada Form Request precisa de 3-4 testes:**
- Validação de campos obrigatórios
- Validação de formato
- Dados válidos
- Autorização (se aplicável)

Estimado: **~10 Form Requests × 3 testes = ~30 novos testes**

### 4️⃣ **Melhorias em Componentes Livewire**
Atualmente: **MenusTable 100%, ModulesTable 87.9%**

- [ ] Aumentar ModulesTable para 100% (+2-3 testes)
- [ ] Criar testes para outros componentes Livewire (se existirem)

Estimado: **~3-5 novos testes**

## 📊 Resumo de Impacto

| Categoria | Testes Novos | Impacto na Cobertura |
|-----------|-------------|-------------------|
| Controllers | ~72 | +40-50% |
| Form Requests | ~30 | +10-15% |
| Middleware | ~8 | +5-10% |
| Livewire Components | ~5 | +2-5% |
| **TOTAL** | **~115** | **+57-80%** |

## 🚀 Sequência Recomendada

1. **Semana 1**: Testes de Controllers (maior impacto)
   - Começar com UserController
   - Depois RoleController
   - Depois PermissionController

2. **Semana 2**: Testes de Form Requests
   - Paralelo com Controllers
   - Validação e autorização

3. **Semana 3**: Middleware e ajustes finais
   - Completar CheckPermission
   - Ajustes menores em Livewire

## ✅ Checklist por Tipo de Teste

### Testes de Controller Feature
```
- [ ] GET /resources (index)
- [ ] GET /resources/create (show form)
- [ ] POST /resources (store)
- [ ] GET /resources/{id}/edit (edit form)
- [ ] PUT/PATCH /resources/{id} (update)
- [ ] DELETE /resources/{id} (destroy)
- [ ] Testa autenticação (401)
- [ ] Testa autorização (403)
- [ ] Testa validação (422)
```

### Testes de Form Request
```
- [ ] Campo obrigatório ausente
- [ ] Validação de tipo/formato
- [ ] Validação de comprimento
- [ ] Validação de unicidade
- [ ] Autorização do usuário
- [ ] Dados válidos passam
```

### Testes de Middleware
```
- [ ] User autenticado com permissão → passa
- [ ] User autenticado sem permissão → 403
- [ ] User não autenticado → redireciona
- [ ] Admin sempre passa → true
```

## 📈 Meta Final
- ✅ **162 testes atuais**
- 📝 **+~115 novos testes**
- 🎯 **~277 testes totais**
- 📊 **+57-80% na cobertura**
- 🏆 **Meta: 90%+ de cobertura global**

---

**Próximo passo**: Começar com UserController? Confirme e vamos começar! 🚀
