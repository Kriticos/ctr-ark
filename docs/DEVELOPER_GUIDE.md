# 🛠️ Guia do Desenvolvedor – LaraSaaS

Bem-vindo ao guia oficial de desenvolvimento do LaraSaaS.

Este documento define regras técnicas, ferramentas, fluxos e o padrão de qualidade esperado no projeto.
Ele documenta o que é permitido fazer no código e como manter o projeto sustentável no longo prazo.

---

## 📋 Índice

- [🛠️ Guia do Desenvolvedor – LaraSaaS](#️-guia-do-desenvolvedor--larasaas)
  - [📋 Índice](#-índice)
  - [🧭 Princípios do Projeto](#-princípios-do-projeto)
  - [🚀 Configuração Inicial](#-configuração-inicial)
  - [🔧 Ferramentas de Qualidade](#-ferramentas-de-qualidade)
    - [🧪 **Pest** - Framework de Testes](#-pest---framework-de-testes)
    - [🎨 **Laravel Pint** - Formatação de Código](#-laravel-pint---formatação-de-código)
    - [🔍 **Larastan + PHPStan** - Análise Estática](#-larastan--phpstan---análise-estática)
    - [📊 **SonarQube** - Análise de Qualidade Contínua](#-sonarqube---análise-de-qualidade-contínua)
  - [🔍 PHPStan – Estratégia Oficial](#-phpstan--estratégia-oficial)
    - [Objetivo](#objetivo)
    - [O que NÃO fazemos](#o-que-não-fazemos)
    - [Configurações Oficiais](#configurações-oficiais)
    - [Execução (Laravel Sail)](#execução-laravel-sail)
  - [🔒 Pre-commit Hooks](#-pre-commit-hooks)
    - [Comportamento](#comportamento)
  - [🚦 Pipeline de CI/CD](#-pipeline-de-cicd)
  - [🧱 Boas Práticas de Código](#-boas-práticas-de-código)
    - [Controllers](#controllers)
    - [Services e Actions](#services-e-actions)
    - [Tipagem](#tipagem)
  - [🔄 Workflow de Desenvolvimento](#-workflow-de-desenvolvimento)
  - [🧪 Testes](#-testes)
    - [Diretrizes](#diretrizes)
  - [🧰 Troubleshooting](#-troubleshooting)
    - [PHPStan falhando](#phpstan-falhando)
    - [Pint falhando](#pint-falhando)
  - [🛣️ Roadmap de Qualidade](#️-roadmap-de-qualidade)
  - [🧠 Mensagem Final](#-mensagem-final)

---

## 🧭 Princípios do Projeto

- Código legível é mais importante do que código “esperto”
- Erros devem ser corrigidos, nunca escondidos
- Não utilizamos baseline para mascarar problemas
- Ferramentas existem para proteger o futuro do código
- Controllers são adaptadores, não donos da regra de negócio
- Lógica reutilizável deve ser isolada no Core

---

## 🚀 Configuração Inicial

```bash
    git clone https://github.com/blackskulp/larasaas.git
    cd larasaas

    composer setup
    ./vendor/bin/sail up -d
    ./vendor/bin/sail artisan migrate --seed
```
    
O comando `composer setup` automaticamente:
- ✅ Instala todas as dependências do Composer
- ✅ Configura os pre-commit hooks no Git
- ✅ Cria o arquivo `.env` (se não existir)
- ✅ Gera a chave da aplicação

---

## 🔧 Ferramentas de Qualidade

### 🧪 **Pest** - Framework de Testes
- Testes de comportamento e regressão
- Alta cobertura em código crítico
- Base para refactors seguros

**Documentação:** [pestphp.com](https://pestphp.com)

### 🎨 **Laravel Pint** - Formatação de Código
- PSR-12 + padrão Laravel
- Sempre obrigatório
- Bloqueia commit se falhar

**Documentação:** [laravel.com/docs/pint](https://laravel.com/docs/pint)

### 🔍 **Larastan + PHPStan** - Análise Estática

**Objetivo:** Detectar bugs e problemas de tipagem antes da execução.

**Características:**
- Detecta erros antes da execução
- Garante consistência estrutural
- Utilizado sem baseline

**Documentação:** 
- [larastan.com](https://larastan.com)
- [phpstan.org](https://phpstan.org)

### 📊 **SonarQube** - Análise de Qualidade Contínua

- Métricas de qualidade, segurança e tendências
- Complementa o PHPStan (não substitui)

**Objetivo:** Monitorar qualidade do código, segurança, duplicação e tendências.

**Características:**
- Dashboard visual com métricas de qualidade
- Detecta bugs, vulnerabilidades, code smells
- Rastreia cobertura de testes e duplicação
- Análise de complexidade ciclomática
- Quality Gates customizáveis
- Histórico e tendências ao longo do tempo

**Complemento às outras ferramentas:**
- PHPStan foca em **tipos**
- Pint foca em **formatação**
- SonarQube foca em **qualidade geral**, segurança e tendências

**Documentação Completa:** [SonarQube Guide](SONARQUBE.md)

---

## 🔍 PHPStan – Estratégia Oficial

### Objetivo
Detectar erros reais e evitar dívida técnica desde o início do projeto.

### O que NÃO fazemos
- Não usamos baseline
- Não silenciamos erros sem correção
- Não usamos ignore genérico

### Configurações Oficiais

Arquivo: phpstan.neon  
Uso: Desenvolvimento diário  
Nível: 4  

Arquivo: phpstan-tests.neon  
Uso: Testes  
Nível: 3  

Arquivo: phpstan-core.neon  
Uso: Core do SaaS (quando existir)  
Nível: 6  

Arquivo: phpstan-strict.neon  
Uso: Auditoria  
Nível: 6  

### Execução (Laravel Sail)

    ./vendor/bin/sail php vendor/bin/phpstan analyse
    ./vendor/bin/sail php vendor/bin/phpstan analyse -c phpstan-strict.neon

Regra: se o PHPStan apontar erro, ele deve ser corrigido no código.

---

## 🔒 Pre-commit Hooks

### Comportamento

- Pint é obrigatório e bloqueia commit
- PHPStan roda em modo informativo durante a fase de estabilização
- O CI é a autoridade final para aprovação

Bypass de emergência:

    git commit --no-verify

Uso apenas em situações excepcionais.

---

## 🚦 Pipeline de CI/CD

O pipeline executa obrigatoriamente:

    vendor/bin/pint --test
    vendor/bin/phpstan analyse
    vendor/bin/pest --coverage --min=90

CI vermelho bloqueia merge.
CI verde indica código confiável.

---

## 🧱 Boas Práticas de Código

### Controllers
- Devem ser finos
- Delegam lógica para Actions ou Services
- Não contêm regra de negócio

### Services e Actions
- Contêm lógica reutilizável
- São facilmente testáveis
- Não dependem de HTTP ou camada web

### Tipagem
- Sempre usar tipos de retorno
- Usar PHPDoc quando necessário
- Preferir DTOs a arrays soltos

---

## 🔄 Workflow de Desenvolvimento

    git checkout -b feat/nova-feature

    composer pint
    composer phpstan
    composer test

    git commit -m "feat: descrição clara"
    git push

---

## 🧪 Testes

### Diretrizes
- Core, Services e Actions devem ter alta cobertura
- Controllers focam em comportamento
- Testes não precisam de tipagem perfeita

Execução:

    ./vendor/bin/sail test
    ./vendor/bin/sail test --coverage --min=90

---

## 🧰 Troubleshooting

### PHPStan falhando
- Corrija o código
- Não crie baseline
- Não silencie erro sem justificativa técnica

### Pint falhando

    ./vendor/bin/pint
    git add .

---

## 🛣️ Roadmap de Qualidade

1. Pint configurado
2. PHPStan sem baseline
3. CI com quality gate
4. Estabilizar código existente
5. Introduzir Core (Services / Actions)
6. Mutation Testing (Infection)
7. Testes E2E (Dusk)

---

## 🧠 Mensagem Final

Este projeto prioriza clareza, previsibilidade e sustentabilidade.

Se uma decisão técnica:
- cria dívida
- esconde erro
- dificulta evolução

ela não é aceita.

