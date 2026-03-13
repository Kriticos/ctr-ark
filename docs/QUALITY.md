# Fase de Qualidade de Código

Este documento resume as ferramentas e processos adotados para elevar a qualidade do código ao máximo no projeto.

## Ferramentas
- **Pest**: framework de testes com cobertura mínima de 90% (alcançado 100%).
- **Larastan (PHPStan)**: análise estática profunda das APIs do Laravel (nível 7).
- **Laravel Pint**: formatação e estilo PSR-12 + preset Laravel.
- **CI (GitHub Actions)**: pipeline de build, análise e testes com cobertura.

## Como rodar localmente
```bash
# Formatação
composer pint

# Análise Estática (Larastan)
composer phpstan

# Testes
composer test

# Testes com cobertura mínima
composer test:coverage
```

## Pipeline de CI
O workflow em `.github/workflows/ci.yml` executa:
- Instalação de dependências e configuração do MySQL
- `php artisan migrate --force`
- `vendor/bin/pint --test`
- `vendor/bin/phpstan analyse`
- `vendor/bin/pest --coverage --min=90`

## Diretrizes
- Manter controllers finos; regras de negócio em Services/Actions
- Policies/Gates para autorização (ACL baseada em rotas)
- Evitar N+1 com eager loading
- Tipagem e retornos explícitos em PHP 8+
- Documentar decisões significativas (ex.: remoção de código morto em `DynamicMenu`)

## Próximos passos sugeridos
- **Dusk** para testes de browser dos fluxos admin
- **Mutation Testing (Infection)** para validar robustez dos testes
- **Rector** para refatorações e upgrades automáticos
