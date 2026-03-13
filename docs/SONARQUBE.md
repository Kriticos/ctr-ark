# 🔍 Integração com SonarQube

Este guia explica como configurar e usar o SonarQube para análise de qualidade de código no LaraSaaS.

## 📋 Índice

1. [O que é SonarQube](#o-que-é-sonarqube)
2. [Configuração](#configuração)
3. [Comandos Locais](#comandos-locais)
4. [Integração CI/CD](#integração-cicd)
5. [Métricas e Relatórios](#métricas-e-relatórios)
6. [Troubleshooting](#troubleshooting)

---

## 🎯 O que é SonarQube

O SonarQube é uma plataforma de código aberto para **inspeção contínua de qualidade de código**. Ele realiza análises automáticas para detectar:

- 🐛 **Bugs** - Problemas que podem causar erros
- 🔒 **Vulnerabilidades** - Questões de segurança
- 💩 **Code Smells** - Problemas de manutenibilidade
- 📊 **Cobertura de Testes** - Porcentagem de código testado
- 🔄 **Duplicação** - Código duplicado
- 📈 **Complexidade** - Complexidade ciclomática

### Complemento às Ferramentas Existentes

O SonarQube **complementa** (não substitui) as ferramentas já utilizadas:

| Ferramenta | Foco | SonarQube Adiciona |
|------------|------|-------------------|
| **PHPStan** | Análise estática de tipos | Vulnerabilidades, code smells, métricas |
| **Pint** | Formatação de código | Complexidade, duplicação, coverage |
| **Pest** | Testes e cobertura | Dashboard visual, histórico, tendências |

---

## ⚙️ Configuração

### 1. Pré-requisitos

Certifique-se de ter
- ✅ SonarQube rodando 
- ✅ SonarScanner CLI instalado

#### Instalar SonarScanner (se necessário)

**Linux/macOS:**
```bash
# Baixar e instalar SonarScanner
wget https://binaries.sonarsource.com/Distribution/sonar-scanner-cli/sonar-scanner-cli-5.0.1.3006-linux.zip
unzip sonar-scanner-cli-5.0.1.3006-linux.zip
sudo mv sonar-scanner-5.0.1.3006-linux /opt/sonar-scanner
sudo ln -s /opt/sonar-scanner/bin/sonar-scanner /usr/local/bin/sonar-scanner

# Verificar instalação
sonar-scanner --version
```

**Via Docker (alternativa):**
```bash
# Criar alias no shell
alias sonar-scanner='docker run --rm -v "$(pwd):/usr/src" sonarsource/sonar-scanner-cli'
```

---

### 2. Arquivo de Configuração

O arquivo `sonar-project.properties` já está configurado na raiz do projeto com:

```properties
# Identificação
sonar.projectKey=larasaas
sonar.projectName=LaraSaaS

# Diretórios
sonar.sources=app,routes,config,database
sonar.tests=tests

# Coverage (gerado pelo Pest)
sonar.php.coverage.reportPaths=coverage.xml

# Exclusões
sonar.exclusions=**/vendor/**,**/node_modules/**,**/*.blade.php
```

---

### 3. Configurar via .env (Recomendado)

1. **Acesse o SonarQube** (ex: http://localhost:9001)
2. **Login** → My Account → Security → **Generate Token**
3. **Edite seu `.env`** e adicione:
    ```dotenv
    SONAR_HOST_URL=http://localhost:9000
    SONAR_LOGIN=SEU_TOKEN_AQUI
    ```
4. **Execute o scanner via Composer**:
    ```bash
    composer test:coverage-report
    composer sonar
    ```

---

## 💻 Comandos Locais

### Executar Análise Completa

```bash
# 1. Gerar relatório de coverage
composer test:coverage-report

# 2. Executar análise do SonarQube
sonar-scanner \
  -Dsonar.projectKey=larasaas \
  -Dsonar.sources=. \
  -Dsonar.host.url=$SONAR_HOST_URL \
  -Dsonar.token=$SONAR_TOKEN

# OU usar o script do composer
composer sonar
```

### Via Docker (se não tiver SonarScanner instalado)

```bash
# 1. Gerar coverage
composer test:coverage-report

# 2. Executar scan via Docker
docker run --rm \
  -v "$(pwd):/usr/src" \
  -e SONAR_HOST_URL=$SONAR_HOST_URL \
  -e SONAR_TOKEN=$SONAR_TOKEN \
  sonarsource/sonar-scanner-cli
```

### Verificar Resultados

Após a análise, acesse:
```
http://seu-sonar.com:9000/dashboard?id=larasaas
```

---

## 🚀 Integração CI/CD

### ⚠️ Análise Local Apenas

**Importante:** Como o SonarQube está rodando em localhost, a análise **não é executada automaticamente no CI/CD** do GitHub Actions.

A integração com CI requer:
- **SonarCloud** (gratuito para projetos open source) 
- **SonarQube exposto publicamente** (não recomendado para localhost)
- **Self-hosted runner** do GitHub Actions na sua rede local

### Fluxo Atual (Local)

O workflow de qualidade no CI executa:
1. ✅ **Pint** - Formatação de código
2. ✅ **PHPStan** - Análise estática
3. ✅ **Pest** - Testes com cobertura mínima de 90%

**SonarQube deve ser executado manualmente** após clonar o projeto ou antes de abrir PRs:

```bash
# 1. Gerar coverage
composer test:coverage-report

# 2. Executar análise local
composer sonar

# 3. Verificar resultados
# Abrir: http://localhost:9000/dashboard?id=larasaas
```

---

### 🌐 Alternativa: SonarCloud (Opcional)

Se quiser integração automática com GitHub Actions, considere usar o **SonarCloud**:

**Vantagens:**
- ✅ Gratuito para projetos open source
- ✅ Integração nativa com GitHub
- ✅ Análise automática em PRs
- ✅ Badges de qualidade no README

**Configuração:**
1. Acesse [sonarcloud.io](https://sonarcloud.io)
2. Conecte sua conta GitHub
3. Importe o repositório `larasaas`
4. Configure secrets no GitHub:
   - `SONAR_TOKEN` (do SonarCloud)
5. Atualize `sonar-project.properties`:
   ```properties
   sonar.organization=seu-username
   sonar.host.url=https://sonarcloud.io
   ```
6. Adicione ao CI (`.github/workflows/ci.yml`):
   ```yaml
   - name: SonarCloud Scan
     uses: SonarSource/sonarcloud-github-action@master
     env:
       GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
       SONAR_TOKEN: ${{ secrets.SONAR_TOKEN }}
   ```

---

### Fluxo Recomendado (Local)

**Desenvolvimento:**
1. Fazer alterações no código
2. Executar verificações locais:
   ```bash
   composer pint
   composer phpstan
   composer test:coverage
   ```
3. **Antes de abrir PR**, executar SonarQube:
   ```bash
   composer test:coverage-report
   composer sonar
   ```
4. Revisar issues no dashboard
5. Corrigir problemas encontrados
6. Commit e push

**CI automatizado (GitHub Actions):**
- ✅ Pint
- ✅ PHPStan  
- ✅ Pest (90% coverage)
- ❌ SonarQube (apenas local)

---

## 📊 Métricas e Relatórios

### Principais Métricas

O SonarQube fornece:

#### 1. **Reliability (Confiabilidade)**
- 🐛 **Bugs**: Problemas que podem causar comportamentos inesperados
- **Rating**: A (0 bugs) até E (muitos bugs)

#### 2. **Security (Segurança)**
- 🔒 **Vulnerabilities**: Falhas de segurança
- 🚨 **Security Hotspots**: Código sensível que precisa revisão
- **Rating**: A (sem vulnerabilidades) até E (críticas)

#### 3. **Maintainability (Manutenibilidade)**
- 💩 **Code Smells**: Problemas que dificultam manutenção
- 🕐 **Technical Debt**: Tempo estimado para resolver problemas
- **Rating**: A (≤5% débito) até E (>50% débito)

#### 4. **Coverage (Cobertura)**
- 📈 **Line Coverage**: % de linhas cobertas por testes
- 🎯 **Branch Coverage**: % de branches testados
- **Meta**: Mínimo 80% (projeto tem 100%!)

#### 5. **Duplications (Duplicação)**
- 🔄 **Duplicated Lines**: % de código duplicado
- **Meta**: <3% é considerado bom

#### 6. **Complexity (Complexidade)**
- 🧩 **Cyclomatic Complexity**: Complexidade do código
- **Meta**: ≤10 por função é recomendado

---

### Dashboard do SonarQube

No dashboard você encontra:

```
┌─────────────────────────────────────┐
│  LaraSaaS Quality Gate: PASSED ✅   │
├─────────────────────────────────────┤
│  Bugs: 0                       A    │
│  Vulnerabilities: 0            A    │
│  Code Smells: 15               A    │
│  Coverage: 100%                ✅   │
│  Duplications: 1.2%            ✅   │
└─────────────────────────────────────┘
```

---

## 🎯 Quality Gates

### O que são Quality Gates?

São **critérios de qualidade** que o código deve passar. Se falhar, o build pode ser bloqueado.

### Configurar Quality Gate

No SonarQube:
1. **Quality Gates** → Create
2. Defina condições:
   ```
   Coverage: ≥ 80%
   New Code Coverage: ≥ 90%
   Bugs: = 0
   Vulnerabilities: = 0
   Security Hotspots Reviewed: = 100%
   Code Smells: ≤ 20
   Duplicated Lines: ≤ 3%
   ```

3. Associar ao projeto **larasaas**

---

## 🔍 Analisando Resultados

### Tipos de Issues

#### 🐛 **Bug (Alto Impacto)**
```php
// ❌ Bug detectado
if ($user->role = 'admin') {  // Deveria ser ==
    // ...
}
```

#### 🔒 **Vulnerability (Segurança)**
```php
// ❌ SQL Injection risk
DB::select("SELECT * FROM users WHERE id = " . $id);

// ✅ Correto (prepared statement)
DB::select("SELECT * FROM users WHERE id = ?", [$id]);
```

#### 💩 **Code Smell (Manutenibilidade)**
```php
// ❌ Função muito complexa (complexity > 15)
public function processData($data) {
    if (...) {
        if (...) {
            foreach (...) {
                if (...) {
                    // ...
                }
            }
        }
    }
}

// ✅ Refatorar em funções menores
public function processData($data) {
    $validated = $this->validateData($data);
    return $this->transformData($validated);
}
```

#### 🔄 **Duplication (Duplicação)**
```php
// ❌ Código duplicado em múltiplos controllers
// UserController
public function store() {
    if (!Auth::check()) {
        return redirect('/login');
    }
    // ...
}

// ProductController
public function store() {
    if (!Auth::check()) {
        return redirect('/login');
    }
    // ...
}

// ✅ Usar middleware
Route::middleware('auth')->group(function () {
    Route::post('users', [UserController::class, 'store']);
    Route::post('products', [ProductController::class, 'store']);
});
```

---

## 🔧 Troubleshooting

### Problema 1: "Sonar-scanner not found"

```bash
# Verificar se está instalado
which sonar-scanner

# Se não, usar via Docker
alias sonar-scanner='docker run --rm -v "$(pwd):/usr/src" sonarsource/sonar-scanner-cli'
```

---

### Problema 2: "Coverage report not found"

```bash
# Garantir que coverage.xml foi gerado
ls -la coverage.xml

# Gerar novamente
composer test:coverage-report

# Verificar caminho no sonar-project.properties
grep coverage.reportPaths sonar-project.properties
```

---

### Problema 3: "Unauthorized (401)"

```bash
# Verificar token
echo $SONAR_TOKEN

# Verificar URL
echo $SONAR_HOST_URL

# Regenerar token no SonarQube se necessário
```

---

### Problema 4: "Quality Gate Failed"

```bash
# Ver detalhes no dashboard do SonarQube
# Identificar qual métrica falhou:
# - Coverage baixo? Adicionar testes
# - Bugs? Corrigir no código
# - Code Smells? Refatorar
# - Duplicação? Extrair para funções/classes compartilhadas
```

---

### Problema 5: "Analysis takes too long"

```bash
# Reduzir escopo temporariamente
sonar-scanner \
  -Dsonar.sources=app \
  -Dsonar.exclusions=**/tests/**

# Aumentar memória do scanner
sonar-scanner -Xmx2048m
```

---

## 📈 Workflow Recomendado

### Desenvolvimento Local

```bash
# 1. Fazer alterações no código
vim app/Models/User.php

# 2. Executar verificações locais
composer pint          # Formatação
composer phpstan       # Análise estática
composer test:coverage # Testes

# 3. (Recomendado antes de PR) Executar SonarQube local
composer test:coverage-report
composer sonar

# 4. Revisar issues no dashboard
# Abrir: http://localhost:9000/dashboard?id=larasaas

# 5. Corrigir problemas encontrados (se houver)

# 6. Commit (pre-commit hooks executam Pint + PHPStan)
git add .
git commit -m "feat: adicionar campo X"

# 7. Push (CI executa Pint + PHPStan + Pest)
git push origin feat/minha-feature
```

**Nota:** O SonarQube **não é executado no CI** por estar em localhost. Execute manualmente antes de abrir PRs importantes.

---

### Revisão de Código

1. **Executar SonarQube local** (antes de abrir PR)
   ```bash
   composer test:coverage-report
   composer sonar
   ```
2. **Revisar issues no dashboard** (http://localhost:9000)
3. **Corrigir problemas críticos** (bugs, vulnerabilidades)
4. **Abrir Pull Request**
5. **CI executa automaticamente:**
   - ✅ Pint passou?
   - ✅ PHPStan passou?
   - ✅ Testes ≥90% passou?
6. **Merge após aprovação**

---

## 🎯 Próximos Passos

### Melhorias Incrementais

1. **Resolver Code Smells** existentes
   - Reduzir complexidade de funções longas
   - Extrair código duplicado
   - Adicionar type hints faltantes

2. **Manter Quality Gate Verde**
   - Não adicionar novos bugs
   - Manter coverage ≥ 90%
   - Revisar security hotspots

3. **Monitorar Tendências**
   - Dashboard do SonarQube mostra evolução
   - Technical debt deve diminuir ao longo do tempo

---

## 📚 Recursos Adicionais

- [Documentação Oficial do SonarQube](https://docs.sonarqube.org)
- [Regras do SonarQube para PHP](https://rules.sonarsource.com/php)
- [Quality Gates](https://docs.sonarqube.org/latest/user-guide/quality-gates/)
- [Guia do Desenvolvedor](DEVELOPER_GUIDE.md)

---

## 🔗 Links Úteis

- **Dashboard Local**: http://localhost:9000
- **Projeto no SonarQube**: http://localhost:9000/dashboard?id=larasaas
- **Issues**: http://localhost:9000/project/issues?id=larasaas
- **Coverage**: http://localhost:9000/component_measures?id=larasaas&metric=coverage

---

**Happy Clean Coding! 🧹✨**
