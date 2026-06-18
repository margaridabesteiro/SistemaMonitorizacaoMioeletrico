# RehabLink — Sistema de Monitorização Mioeléctrica

Plataforma web de telereabilitação para gestão clínica de utentes com prótese mioelétrica. Integra biofeedback com sensores FSR406 (ESP32), jogos de reabilitação gamificados e monitorização remota por profissionais de saúde.

---

## Stack Tecnológica

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.x |
| Base de dados | MariaDB 10.4 (XAMPP) |
| Frontend | Bootstrap 5, FontAwesome 6, Chart.js |
| Hardware | ESP32 + Sensor de força FSR406 |
| Autenticação | Sessões PHP + BCrypt (cost 10) |
| RGPD | Anonimização, exportação e eliminação de dados |

---

## Instalação (XAMPP)

### 1. Copiar o projeto

```
C:\xampp\htdocs\sistema_mioeletrico\SistemaMonitorizacaoMioeletrico\
```

### 2. Importar o schema e o seed

No **phpMyAdmin** ou via linha de comandos:

```bash
# Schema base
mysql -u root sistema_mioeletrico < database/sistema_mioeletrico.sql

# Migrações (executar por ordem)
mysql -u root sistema_mioeletrico < database/migration_seguradoras_precos.sql
mysql -u root sistema_mioeletrico < database/migration_notificacoes.sql
mysql -u root sistema_mioeletrico < database/migration_auditoria.sql
mysql -u root sistema_mioeletrico < database/migration_metodo_pagamento.sql
mysql -u root sistema_mioeletrico < database/migration_deve_alterar_password.sql
# (restantes migrações em database/)

# Seed completo — dados de demonstração
mysql -u root sistema_mioeletrico < database/seed_completo.sql
```

### 3. Verificar configuração

**`config/database.php`**
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_mioeletrico');
```

**`config/app.php`**
```php
define('APP_URL', 'http://localhost/sistema_mioeletrico/SistemaMonitorizacaoMioeletrico');
```

---

## Credenciais de Demonstração

### Administradores — `RehabLink2025!`

| Nome | Email |
|---|---|
| Sofia Mendes | `sofia.mendes@rehablink.pt` |
| Ricardo Sousa | `ricardo.sousa@rehablink.pt` |

### Médicos — `Medico2025!`

| Nome | Email |
|---|---|
| Dra. Ana Silva | `ana.silva@rehablink.pt` |
| Dr. Pedro Costa | `pedro.costa@rehablink.pt` |
| Dra. Margarida Lopes | `margarida.lopes@rehablink.pt` |
| Dr. João Ferreira | `joao.ferreira.med@rehablink.pt` |
| Dra. Catarina Neves | `catarina.neves@rehablink.pt` |
| Dr. Rui Baptista | `rui.baptista@rehablink.pt` |

### Técnicos — `Tecnico2025!`

| Nome | Email |
|---|---|
| Miguel Santos | `miguel.santos@rehablink.pt` |
| Inês Rodrigues | `ines.rodrigues@rehablink.pt` |
| Carlos Pinto | `carlos.pinto@rehablink.pt` |
| Beatriz Cunha | `beatriz.cunha@rehablink.pt` |
| Diogo Almeida | `diogo.almeida@rehablink.pt` |
| Helena Vieira | `helena.vieira@rehablink.pt` |

### Utentes — `Utente2025!`

| Nome | Email |
|---|---|
| João Santos | `joao.santos@rehablink.pt` |
| Maria Oliveira | `maria.oliveira@rehablink.pt` |
| Pedro Ferreira | `pedro.ferreira@rehablink.pt` |
| Ana Sousa | `ana.sousa@rehablink.pt` |
| Carlos Lima | `carlos.lima@rehablink.pt` |
| Diana Alves | `diana.alves@rehablink.pt` |
| Inês Fernandes | `ines.fernandes@rehablink.pt` |
| António Silva | `antonio.silva@rehablink.pt` |
| Beatriz Costa | `beatriz.costa@rehablink.pt` |
| Rui Matos | `rui.matos@rehablink.pt` |
| Lucas Rodrigues | `lucas.rodrigues@rehablink.pt` |
| Sofia Pires | `sofia.pires@rehablink.pt` |

---

## Funcionalidades por Perfil

### Administrador
- Dashboard com KPIs (utentes, sessões, faturas, dispositivos)
- Gestão de utilizadores (criar, editar, ativar/desativar, anonimizar — RGPD)
- Gestão de profissionais de saúde (médicos e técnicos)
- Gestão de dispositivos (5 estados: disponível, emprestado, avariado, perdido, danificado)
- Controlo de faturação com filtros (pagas, pendentes, vencidas, inativas e vencidas)
- Detalhe de fatura com linhas de serviço e registo de pagamento
- Relatórios do sistema (utentes por médico/técnico, sessões, faturação)
- Auditoria RGPD (15 registos/página) e gestão de pedidos RGPD
- Backoffice da landing page (textos e contactos editáveis)
- Notificações internas em tempo real

### Médico
- Agenda mensal com consultas por cores de estado
- Nova consulta com horários da clínica (2ª-6ª 8h–20h, Sáb 9h–13h)
- Detalhe de consulta (tipo, modalidade, evolução)
- Lista de pacientes com programa de tratamento e histórico
- Perfil clínico do paciente (diagnóstico, sessões, métricas)
- Notificações de novos utentes atribuídos

### Técnico
- Dashboard com utentes e sessões do próprio
- Gestão de sessões (calibração, treino, jogo, avaliação funcional)
- Resultado de jogo (percentagem, score, tendência)
- Análise de desempenho com gráficos
- Gestão de empréstimos de dispositivos
- Mensagens internas

### Utente
- Dashboard pessoal com próximas sessões
- Histórico de sessões e consultas
- Resultados de jogos com evolução gráfica
- Documentos e faturas
- Pedidos RGPD (exportação e eliminação de dados)
- Preferências de notificação

---

## Estrutura do Projeto

```
SistemaMonitorizacaoMioeletrico/
├── index.php                    ← Landing page pública
├── config/
│   ├── app.php                  ← Configuração global, helpers, sessão
│   └── database.php             ← PDO singleton
├── includes/                    ← Partials reutilizáveis por perfil
│   ├── header_{perfil}.php
│   ├── sidebar_{perfil}.php
│   ├── footer.php
│   └── notificacoes_bell.php
├── private/
│   ├── login/
│   ├── admin/                   ← backoffice, users, devices, billing, reports...
│   ├── medico/                  ← agenda, consultas, pacientes, prescrições...
│   ├── tecnico/                 ← sessões, jogos, análise, dispositivos...
│   └── utente/                  ← dashboard, histórico, jogos, faturas...
├── api/                         ← Endpoints JSON (ESP32 + AJAX)
│   ├── esp32/sync_sessao.php    ← Recebe dados do sensor FSR406
│   ├── sessoes/                 ← Resultados de jogo, utilizadores do técnico
│   ├── notificacoes/            ← Contagem, marcar lidas
│   ├── admin/                   ← Faturação, RGPD, toggle ativo
│   └── ...
├── public/assets/               ← Bootstrap 5, FontAwesome 6, Chart.js, jQuery
└── database/
    ├── sistema_mioeletrico.sql  ← Schema completo
    ├── migration_*.sql          ← Migrações incrementais
    └── seed_completo.sql        ← Seed de demonstração (v2.0)
```

---

## Base de Dados — Tabelas Principais

| Tabela | Descrição |
|---|---|
| `utilizadores` | Base de todos os perfis (admin / médico / técnico / utente) |
| `profissionais` | Dados extra de médicos e técnicos (ordem, especialidade) |
| `utentes` | Dados clínicos, médico e técnico atribuídos, seguradora |
| `seguradoras` | SNS, Particular, Multicare, AdvanceCare, Médis... |
| `tabela_precos` | Preços por tipo de serviço × seguradora |
| `dispositivos` | Sensores FSR406 (estados: disponivel/emprestado/avariado/perdido/danificado) |
| `emprestimos_dispositivos` | Histórico de empréstimos de dispositivos a utentes |
| `jogos` | Jogos de reabilitação (catch_game, claw_game, flappy_trainer) |
| `sessoes` | Sessões de treino/jogo/calibração por utente e técnico |
| `metricas_sessao` | Percentagem final, score, tendência (melhoria/estavel/regressao) |
| `programas_tratamento` | Prescrição clínica do médico para cada utente |
| `consultas` | Consultas médicas (presencial/vídeo, agendada/realizada/cancelada) |
| `faturas` | Faturação com tipo de serviço, seguradora, método de pagamento |
| `fatura_linhas` | Linhas de detalhe por fatura |
| `mensagens` | Mensagens internas entre utilizadores |
| `notificacoes` | Notificações push internas por utilizador |
| `auditoria` | Registo de ações (RGPD Art. 30.º) |
| `backoffice_conteudo` | Textos editáveis da landing page |

---

## Integração Hardware (ESP32 + FSR406)

O ESP32 envia dados de força ao endpoint `/api/esp32/sync_sessao.php` via HTTP POST com autenticação por token (`token_api` na tabela `dispositivos`). O servidor calcula as métricas e atualiza a sessão em curso.

```
FSR406 → ESP32 → POST /api/esp32/sync_sessao.php → metricas_sessao
```

---

## Segurança

- Todas as páginas privadas protegidas com `requirePerfil('perfil')`
- Outputs HTML escapados com `h()` (proteção XSS)
- Formulários POST com token CSRF (`csrfToken()`)
- Passwords com BCrypt (cost 10)
- Registo de auditoria em todas as ações relevantes
- Anonimização de dados conforme RGPD
