# Setup em Nova VM

Este guia resume o que ajustar ao clonar este projeto em outra VM.

## 1) Pré-requisitos

- Docker + Docker Compose
- Git
- (Opcional) Node.js/NPM no host, se for compilar assets fora do container

## 2) Clonar e entrar no projeto

```bash
git clone <URL_DO_REPOSITORIO> ctr-process
cd ctr-process
```

## 3) Arquivo de ambiente

Crie o `.env` a partir do exemplo:

```bash
cp .env.example .env
```

Ajuste no `.env` pelo menos:

- `APP_URL` (URL/host da VM)
- `APP_PORT` (porta da aplicação no host, ex.: `8080`)
- `FORWARD_DB_PORT` (porta do MySQL do projeto, ex.: `3307` se `3306` já estiver ocupada)
- `FORWARD_DB_TEST_PORT` (porta do MySQL de testes, ex.: `3308`)
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (se quiser diferente do padrão)

## 4) Subir containers

```bash
docker compose up -d
```

Se aparecer erro de porta em uso (ex.: `3306`), altere `FORWARD_DB_PORT` no `.env` e suba novamente.

## 5) Dependências PHP e chave da aplicação

```bash
docker compose exec laravel.test composer install
docker compose exec laravel.test php artisan key:generate
```

## 6) Banco de dados

```bash
docker compose exec laravel.test php artisan migrate --seed
```

## 7) Permissões de diretórios (quando necessário)

Se houver erro de escrita em cache/storage:

```bash
docker compose exec laravel.test chmod -R 775 storage bootstrap/cache
```

## 8) Limpeza de cache (após mudanças de config/view/rotas)

```bash
docker compose exec laravel.test php artisan optimize:clear
```

## 9) Acessar aplicação

- URL: `http://<IP_OU_HOST_DA_VM>:<APP_PORT>`

## 10) Observações específicas deste projeto

- O editor de procedimentos usa **TOAST UI** via CDN (não precisa API key).
- Upload de imagens do editor usa endpoints protegidos do backend.
- Se mudar regras de rota/permissão, rode:

```bash
docker compose exec laravel.test php artisan optimize:clear
```

