# Laravel Scheduler - Sistema de Usuários Online e Limpeza de Sessões

## 📋 Visão Geral

O sistema utiliza o Laravel Scheduler para executar tarefas agendadas automaticamente, similar ao supervisord. Um serviço dedicado no Docker Compose (`scheduler`) executa o comando `schedule:run` a cada minuto.

## ⚙️ Configuração Atual

### Comandos Agendados

#### 1. Atualização de Status Online
**Comando:** `users:update-online-status`  
**Frequência:** A cada 5 minutos  
**Descrição:** Verifica e atualiza estatísticas de usuários online baseado em `last_activity_at`

**O que faz:**
- Marca usuários inativos (entre 5-10 minutos) como offline
- Conta e exibe estatísticas de usuários online
- Conta e exibe estatísticas de usuários inativos
- Garante consistência do status online/offline

#### 2. Limpeza de Sessões Expiradas
**Comando:** `sessions:clean-expired`  
**Frequência:** A cada 10 minutos  
**Descrição:** Limpa sessões expiradas e marca usuários como offline automaticamente

**O que faz:**
- Identifica sessões ativas no banco de dados
- Marca usuários sem sessão ativa como offline
- Remove sessões expiradas do banco de dados
- Previne que usuários com sessão expirada apareçam como online
- Otimiza o uso do banco de dados

### Arquivo de Configuração

O agendamento está definido em: `routes/console.php`

```php
// Agendar atualização de status online dos usuários a cada 5 minutos
Schedule::command('users:update-online-status')->everyFiveMinutes();

// Limpar sessões expiradas e marcar usuários como offline a cada 10 minutos
Schedule::command('sessions:clean-expired')->everyTenMinutes();
```

## 🐳 Serviço Docker (Scheduler)

O `compose.yaml` inclui um serviço dedicado que executa o scheduler:

```yaml
scheduler:
    build:
        context: './vendor/laravel/sail/runtimes/8.4'
        dockerfile: Dockerfile
    command: >
        sh -c "while [ true ]; do
            php /var/www/html/artisan schedule:run --verbose --no-interaction &
            sleep 60
        done"
```

Este serviço:
- Executa `schedule:run` a cada 60 segundos
- Verifica se há comandos agendados prontos para executar
- Roda em background automaticamente

## 🔍 Comandos Úteis

### Executar comandos manualmente (dentro do container Docker)

```bash
# Limpar sessões expiradas e marcar usuários offline
docker compose exec laravel.test php artisan sessions:clean-expired

# Atualizar status online e ver estatísticas
docker compose exec laravel.test php artisan users:update-online-status
```

### Executar comandos com Laravel Sail

```bash
# Limpar sessões expiradas
./vendor/bin/sail artisan sessions:clean-expired

# Atualizar status online
./vendor/bin/sail artisan users:update-online-status
```

### Listar comandos agendados
```bash
./vendor/bin/sail artisan schedule:list
```

### Ver logs do scheduler
```bash
./vendor/bin/sail logs scheduler --tail=50
```

### Ver logs em tempo real
```bash
./vendor/bin/sail logs scheduler -f
```

### Testar o schedule (rodar todos os comandos due)
```bash
./vendor/bin/sail artisan schedule:run
```

### Reiniciar o scheduler
```bash
./vendor/bin/sail restart scheduler
```

## 📊 Como Funciona o Status Online

### Fluxo Completo

1. **Middleware UpdateLastActivity** atualiza `last_activity_at` a cada request autenticado
2. **Método isOnline()** verifica se `last_activity_at` está dentro dos últimos 5 minutos
3. **Comando `users:update-online-status`** roda a cada 5 minutos para garantir consistência
4. **Comando `sessions:clean-expired`** roda a cada 10 minutos para limpar sessões e marcar offline
5. **Cálculo em tempo real** garante precisão sem depender apenas dos comandos

### Sistema de Limpeza de Sessões

#### Por que é necessário?

Quando um usuário:
- **Fecha o navegador sem fazer logout** → Sessão permanece ativa no servidor
- **Sessão expira por inatividade** → Usuário ainda aparece como online até a limpeza
- **Perde conexão com internet** → `last_activity_at` não é atualizado

O comando `sessions:clean-expired` resolve esses casos:

1. **Busca sessões ativas** no banco de dados (baseado no tempo de vida configurado)
2. **Identifica usuários** com sessões ativas
3. **Marca como offline** usuários que aparecem online mas não têm sessão ativa
4. **Remove sessões antigas** do banco para otimizar espaço

#### Tempo de Expiração

O sistema usa o tempo de vida de sessão configurado no Laravel (padrão: 120 minutos):

```php
// config/session.php
'lifetime' => env('SESSION_LIFETIME', 120),
```

**Fluxo de exemplo:**
- Usuário faz login às 10:00
- Usuário fecha o navegador sem logout às 10:05
- Sessão expira automaticamente às 12:05 (2 horas depois)
- Comando `sessions:clean-expired` roda às 12:10
- Usuário é marcado como offline

### Critérios de Status

- **Online**: `last_activity_at` dentro dos últimos 5 minutos
- **Offline recente**: Entre 5 minutos e 7 dias (mostra "Visto há X tempo")
- **Offline**: Mais de 7 dias sem atividade
- **Nunca ativo**: `last_login_at` é null

### Comportamento no Logout

Quando um usuário faz logout explicitamente:
- `last_activity_at` é definido para 10 minutos atrás
- Status é marcado como offline **imediatamente**
- Não precisa esperar pelos comandos agendados

## 🔧 Adicionar Novos Comandos Agendados

Para agendar novos comandos, edite `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

// A cada minuto
Schedule::command('comando:exemplo')->everyMinute();

// A cada 5 minutos
Schedule::command('comando:exemplo')->everyFiveMinutes();

// A cada hora
Schedule::command('comando:exemplo')->hourly();

// Diariamente às 2:00 AM
Schedule::command('comando:exemplo')->dailyAt('02:00');

// Apenas em produção
Schedule::command('comando:exemplo')
    ->hourly()
    ->environments(['production']);
```

## 🚀 Deploy em Produção

Em produção (servidor sem Docker), adicione ao crontab:

```bash
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

Com Sail/Docker, o serviço `scheduler` já cuida disso automaticamente.

## 🐛 Troubleshooting

### Scheduler não está executando

1. Verificar se o serviço está rodando:
```bash
./vendor/bin/sail ps
```

2. Verificar logs:
```bash
./vendor/bin/sail logs scheduler
```

3. Reiniciar o serviço:
```bash
./vendor/bin/sail restart scheduler
```

### Comando não aparece na lista

1. Verificar sintaxe em `routes/console.php`
2. Limpar cache:
```bash
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear
```

### Comando não executa no horário esperado

1. Verificar timezone no `.env`:
```env
APP_TIMEZONE=America/Sao_Paulo
```

2. Confirmar horário no container:
```bash
./vendor/bin/sail exec laravel.test date
```

## 📚 Referências

- [Laravel Task Scheduling](https://laravel.com/docs/scheduling)
- [Laravel Sail Documentation](https://laravel.com/docs/sail)
- [Cron Expression Generator](https://crontab.guru/)
