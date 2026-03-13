# Configuração de Banco de Dados para Testes

## Estrutura

O projeto possui dois containers MySQL separados:

### 1. MySQL Principal (`mysql`)
- **Porta**: 3306 (host) → 3306 (container)
- **Banco**: `larasaas`
- **Uso**: Desenvolvimento e aplicação principal
- **Volume**: `sail-mysql`

### 2. MySQL de Testes (`mysql.test`)
- **Porta**: 3307 (host) → 3306 (container)
- **Banco**: `testing`
- **Uso**: Execução de testes (PHPUnit/Pest)
- **Volume**: `sail-mysql-test`

## Variáveis de Ambiente

Adicione ao seu `.env`:

```env
FORWARD_DB_PORT=3306
FORWARD_DB_TEST_PORT=3307
```

## Executando Testes

### Via Sail
```bash
./vendor/bin/sail test
```

### Com filtro
```bash
./vendor/bin/sail test --filter=UserTest
```

### Com coverage
```bash
./vendor/bin/sail test --coverage
```

### Com coverage e limite mínimo
```bash
./vendor/bin/sail test --coverage --min=90
```

## Code Coverage

A cobertura de código (code coverage) mede quanto do código-fonte é testado pelos testes automatizados.

### Objetivo de Coverage

- **Mínimo requerido**: 90%
- **Ideal**: 100%
- **Cobertura por tipo**: Models, Controllers, Middleware, etc.

### Gerando Coverage

#### Coverage Padrão
```bash
./vendor/bin/sail test --coverage
```

Gera um relatório HTML em `storage/coverage/` com detalhamento de cada arquivo.

#### Coverage com Limite Mínimo (Requerido)
```bash
./vendor/bin/sail test --coverage --min=90
```

Este comando:
- Executa todos os testes
- Gera o relatório de cobertura
- **Falha se a cobertura for menor que 90%**
- Mostra quais linhas não estão testadas

#### Coverage de um Arquivo Específico
```bash
./vendor/bin/sail test --coverage --path=app/Models/User.php
```

#### Coverage com Profile (Testes mais lentos)
```bash
./vendor/bin/sail test --coverage --profile
```

Mostra os 10 testes mais lentos ao final.

### Verificando Coverage Localmente

Após gerar o coverage, abra o relatório HTML:

```bash
open storage/coverage/index.html  # macOS
xdg-open storage/coverage/index.html  # Linux
start storage/coverage/index.html  # Windows
```

Ou acesse pelo navegador: `file:///caminho/do/projeto/storage/coverage/index.html`

### Interpretando o Relatório

O relatório mostra:
- **Percentage**: Percentual de linhas testadas
- **Lines**: Total de linhas / Linhas testadas
- **Methods**: Total de métodos / Métodos testados
- **Classes**: Total de classes / Classes testadas

Cores no relatório:
- 🟢 **Verde**: Linhas/métodos testados (cobertas)
- 🔴 **Vermelho**: Linhas/métodos não testados (não cobertas)
- 🟡 **Amarelo**: Código com cobertura parcial

### Aumentando a Cobertura

Para atingir 90% de cobertura:

1. **Identificar código não testado**: Procure por linhas vermelhas no relatório
2. **Escrever testes**: Adicione testes para as linhas não cobertas
3. **Re-executar**: `./vendor/bin/sail test --coverage --min=90`
4. **Iterar**: Repita até atingir 90%

### Coverage nas CI/CD

Em pipelines de integração contínua, sempre use:

```bash
./vendor/bin/sail test --coverage --min=90
```

Isso garante que nenhum código com cobertura insuficiente seja mergeado na branch principal.

## Configuração PHPUnit/Pest

O arquivo `phpunit.xml` está configurado para usar automaticamente o banco de testes:

```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_HOST" value="mysql.test"/>
<env name="DB_PORT" value="3306"/>
<env name="DB_DATABASE" value="testing"/>
```

### Configuração de Coverage no phpunit.xml

Para adicionar restrição de cobertura mínima no `phpunit.xml`:

```xml
<coverage processUncoveredFiles="true">
    <report>
        <html outputDirectory="storage/coverage"/>
    </report>
    <report>
        <text outputFile="php://stdout" lowUpperBound="85" highLowerBound="90"/>
    </report>
</coverage>
```

Isso mostrará um aviso se a cobertura cair abaixo de 85% ou 90%.

## Migrations para Testes

Os testes podem usar o trait `RefreshDatabase` para garantir um banco limpo:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('example test', function () {
    // Seu teste aqui
});
```

## Acessando o Banco de Testes

### Via linha de comando
```bash
./vendor/bin/sail exec mysql.test mysql -u sail -ppassword testing
```

### Via MySQL Client externo
- **Host**: localhost
- **Porta**: 3307
- **Usuário**: sail
- **Senha**: password
- **Banco**: testing

## Limpando Volumes

Para resetar completamente os bancos de dados:

```bash
./vendor/bin/sail down -v
./vendor/bin/sail up -d
```

## Benefícios

✅ **Isolamento**: Testes não afetam dados de desenvolvimento
✅ **Performance**: Bancos separados evitam conflitos
✅ **Segurança**: Dados de testes podem ser resetados sem medo
✅ **CI/CD**: Fácil integração com pipelines de testes
