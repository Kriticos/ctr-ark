# 🎨 Componente Icon Picker

## Visão Geral

O componente `icon-picker` permite selecionar ícones visualmente através de uma interface gráfica intuitiva, melhorando significativamente a experiência do usuário ao gerenciar módulos.

## Características

✅ **Múltiplos Fornecedores**: Suporta Font Awesome, Bootstrap Icons e Heroicons  
✅ **Interface Visual**: Grid de ícones clicável com preview  
✅ **Busca em Tempo Real**: Campo de pesquisa para filtrar ícones  
✅ **Preview Dinâmico**: Mostra o ícone selecionado em tempo real  
✅ **Responsivo**: Grade adaptável (6-12 colunas dependendo do tamanho da tela)  
✅ **Dark Mode**: Suporte completo ao tema escuro  
✅ **Limitado e Curado**: Lista apenas os ícones mais populares para facilitar escolha  

## Estrutura de Arquivos

```
public/
└── data/
    └── icons.json          # Lista de ícones por fornecedor

resources/
└── views/
    └── components/
        └── icon-picker.blade.php   # Componente Blade
```

## Uso Básico

### Em Formulários Blade

```blade
<x-icon-picker 
    name="icon" 
    :value="old('icon', '')" 
    label="Selecione um Ícone"
    provider="fontawesome"
/>
```

### Parâmetros

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `name` | string | 'icon' | Nome do campo no formulário |
| `value` | string | '' | Valor inicial (ícone pré-selecionado) |
| `label` | string | 'Ícone' | Label exibido acima do componente |
| `provider` | string | 'fontawesome' | Fornecedor padrão: `fontawesome`, `bootstrap`, `heroicons` |
| `required` | boolean | false | Se o campo é obrigatório |

### Exemplo Completo (Create)

```blade
<form action="{{ route('admin.modules.store') }}" method="POST">
    @csrf
    
    <x-icon-picker 
        name="icon" 
        :value="old('icon', '')" 
        label="Selecione um Ícone"
        provider="fontawesome"
        :required="true"
    />
    
    @error('icon')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
    
    <button type="submit">Salvar</button>
</form>
```

### Exemplo com Valor Existente (Edit)

```blade
<x-icon-picker 
    name="icon" 
    :value="old('icon', $module->icon ?? '')" 
    label="Selecione um Ícone"
    provider="fontawesome"
/>
```

## Estrutura do JSON

`public/data/icons.json`:

```json
{
  "fontawesome": {
    "name": "Font Awesome",
    "prefix": "fa-",
    "icons": [
      "home",
      "users",
      "cog",
      "chart-bar",
      ...
    ]
  },
  "bootstrap": {
    "name": "Bootstrap Icons",
    "prefix": "bi-",
    "icons": [
      "house",
      "people",
      "gear",
      ...
    ]
  },
  "heroicons": {
    "name": "Heroicons",
    "prefix": "heroicon-",
    "icons": [
      "home",
      "users",
      "cog",
      ...
    ]
  }
}
```

### Adicionar Novos Ícones

Para adicionar novos ícones à lista:

1. Edite `public/data/icons.json`
2. Adicione o nome do ícone (sem prefixo) ao array `icons` do fornecedor desejado
3. Exemplo:

```json
{
  "fontawesome": {
    "name": "Font Awesome",
    "prefix": "fa-",
    "icons": [
      "home",
      "users",
      "meu-novo-icone"  // ← Adicione aqui
    ]
  }
}
```

### Adicionar Novo Fornecedor

Para adicionar um novo fornecedor de ícones:

```json
{
  "meu-fornecedor": {
    "name": "Meu Fornecedor",
    "prefix": "mf-",
    "icons": [
      "icon1",
      "icon2",
      "icon3"
    ]
  }
}
```

Então atualize o método `getIconClass()` no componente se necessário.

## Funcionalidades

### 1. Alternância de Fornecedor

O usuário pode alternar entre Font Awesome, Bootstrap Icons e Heroicons clicando nos botões superiores.

### 2. Preview em Tempo Real

O ícone selecionado é exibido em um card de preview com:
- Visualização grande do ícone (64x64px)
- Nome do ícone em formato monospace
- Botão "Limpar" para resetar seleção

### 3. Busca de Ícones

Campo de busca que filtra os ícones em tempo real conforme o usuário digita.

```javascript
// Busca case-insensitive
searchTerm: 'home' → filtra apenas ícones com "home" no nome
```

### 4. Grid Responsivo

- Mobile (< 640px): 6 colunas
- Tablet (640px - 768px): 8 colunas
- Desktop (768px - 1024px): 10 colunas
- Large Desktop (> 1024px): 12 colunas

### 5. Seleção Visual

- Ícone selecionado: Fundo azul + borda
- Hover: Escurecimento suave
- Tooltip: Mostra nome do ícone ao passar o mouse

## JavaScript (Alpine.js)

O componente usa Alpine.js para interatividade:

```javascript
function iconPicker(config) {
    return {
        name: config.name,           // Nome do campo
        selectedIcon: config.value,  // Ícone selecionado
        currentProvider: config.provider, // Fornecedor atual
        providers: {},               // Dados dos fornecedores
        searchTerm: '',             // Termo de busca
        
        async init() {
            await this.loadProviders(); // Carrega JSON
        },
        
        selectIcon(icon) {
            this.selectedIcon = icon;  // Seleciona ícone
        },
        
        switchProvider(provider) {
            this.currentProvider = provider; // Troca fornecedor
            this.selectedIcon = '';         // Limpa seleção
        }
    }
}
```

## Integrações

### CDNs Necessários

Adicione ao `<head>` do layout:

```blade
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
```

### Alpine.js

O componente já usa Alpine.js que está incluído no Livewire/Flux.

## Salvando no Banco

O componente gera um campo hidden com o nome do ícone:

```html
<input type="hidden" name="icon" value="users">
```

### No Controller

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'icon' => 'nullable|string|max:50',
    ]);
    
    Module::create([
        'name' => $request->name,
        'icon' => $request->icon, // Apenas o nome: "users"
        // ...
    ]);
}
```

### Exibindo o Ícone

No banco, salve apenas o nome do ícone (ex: "users"). Na view, use com o prefixo:

```blade
{{-- Font Awesome --}}
<i class="fas fa-{{ $module->icon }}"></i>

{{-- Bootstrap Icons --}}
<i class="bi bi-{{ $module->icon }}"></i>

{{-- Ou crie um helper --}}
@php
    $iconClass = match($module->icon_provider ?? 'fontawesome') {
        'fontawesome' => 'fas fa-' . $module->icon,
        'bootstrap' => 'bi bi-' . $module->icon,
        'heroicons' => 'heroicon-o-' . $module->icon,
    };
@endphp
<i class="{{ $iconClass }}"></i>
```

## Melhorias Futuras

- [ ] Upload de ícones customizados
- [ ] Lazy loading de ícones (paginação)
- [ ] Favoritos por usuário
- [ ] Preview com diferentes tamanhos
- [ ] Suporte a SVG inline
- [ ] Histórico de ícones recentemente usados
- [ ] Categorias de ícones
- [ ] Copiar código do ícone

## Troubleshooting

### Ícones não aparecem

**Problema**: Grid vazio ou ícones não renderizam

**Solução**:
1. Verifique se `public/data/icons.json` existe
2. Certifique-se de que os CDNs estão carregados
3. Limpe cache: `sail artisan view:clear`

### JSON não carrega

**Problema**: Erro de fetch no console

**Solução**:
```bash
# Verifique permissões
chmod 644 public/data/icons.json

# Certifique-se de que o arquivo está acessível
curl http://localhost:8080/data/icons.json
```

### Ícones não aparecem na interface

**Problema**: Classes CSS não geram ícones

**Solução**:
1. Verifique se Font Awesome/Bootstrap Icons estão no `<head>`
2. Inspecione elemento para ver as classes aplicadas
3. Teste em um ícone isolado

### Não salva no banco

**Problema**: Valor vazio após submit

**Solução**:
```blade
{{-- Certifique-se de que o name está correto --}}
<x-icon-picker name="icon" />

{{-- Verifique o request no controller --}}
dd($request->all());
```

## Exemplos de Uso

### Módulo de Usuários

```blade
<x-icon-picker 
    name="icon" 
    value="users" 
    label="Ícone do Módulo"
    provider="fontawesome"
/>
```

### Módulo de Relatórios

```blade
<x-icon-picker 
    name="icon" 
    value="chart-bar" 
    label="Escolha um Ícone"
    provider="fontawesome"
/>
```

### Categorias de Produtos

```blade
<x-icon-picker 
    name="category_icon" 
    :value="$category->icon ?? 'tag'" 
    label="Ícone da Categoria"
    provider="bootstrap"
/>
```

---

**Desenvolvido para**: LaraSaaS  
**Versão**: 1.0  
**Última atualização**: 30/11/2025
