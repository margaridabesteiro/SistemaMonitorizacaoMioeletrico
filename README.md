# RehabLink — Sistema de Monitorização Mioeléctrica
## Guia de Migração HTML → PHP + MySQL (XAMPP)

---

## Estrutura de Pastas Final

```
rehablink/                          ← pasta raiz em htdocs/
│
├── index.php                       ← landing page pública
├── .htaccess                       ← segurança + routing
│
├── config/
│   ├── app.php                     ← configuração global, sessão, helpers
│   └── database.php                ← ligação PDO (singleton)
│
├── includes/                       ← partials reutilizáveis (PHP includes)
│   ├── header_admin.php
│   ├── header_medico.php
│   ├── header_tecnico.php
│   ├── header_utente.php
│   ├── sidebar_admin.php
│   ├── sidebar_medico.php
│   ├── sidebar_tecnico.php
│   ├── sidebar_utente.php
│   └── footer.php
│
├── private/                        ← área autenticada
│   ├── login/
│   │   └── login.php
│   ├── admin/
│   │   ├── index_admin.php
│   │   ├── utilizadores/
│   │   │   ├── lista_utilizadores.php
│   │   │   ├── novo_utilizador.php
│   │   │   └── editar_utilizador.php  ← a criar (mesmo padrão)
│   │   ├── profissionais_saude/    ← mesmo padrão CRUD
│   │   ├── dispositivos/
│   │   ├── faturacao/
│   │   ├── relatorios/
│   │   ├── seguranca/
│   │   ├── configuracao/
│   │   └── backoffice/
│   ├── medico/
│   │   ├── index_M.php
│   │   ├── consultas/
│   │   ├── prescricoes/
│   │   ├── pacientes/
│   │   └── exames/
│   ├── tecnico/
│   │   ├── index_F.php             ← a criar (mesmo padrão que index_M.php)
│   │   ├── sessoes/
│   │   │   └── lista_sessoes.php
│   │   ├── pacientes/
│   │   ├── analise/
│   │   ├── mensagens/
│   │   ├── relatorios/
│   │   ├── jogos/
│   │   ├── config/
│   │   └── ajuda/
│   └── utente/
│       ├── index_utente.php
│       ├── sessoes_agendadas.php   ← a criar
│       ├── historico_sessoes.php   ← a criar
│       ├── jogos_reabilitacao.php  ← a criar
│       ├── mensagens.php           ← a criar
│       ├── pagamentos.php          ← a criar
│       └── detalhes.php           ← a criar
│
├── api/                            ← endpoints JSON (sem HTML)
│   ├── auth/
│   │   └── logout.php
│   ├── admin/
│   │   └── utilizadores/
│   │       └── toggle_ativo.php
│   └── sessoes/
│       └── leituras.php           ← recebe dados EMG do ESP32
│
├── public/                         ← assets estáticos (CSS, JS, imagens)
│   └── assets/
│       ├── bootstrap/
│       ├── fontawesome/
│       ├── jQuery/
│       ├── datatables/
│       ├── css/
│       │   ├── common.css
│       │   ├── admin.css
│       │   ├── medico.css
│       │   ├── fisioterapeuta.css
│       │   └── utente.css
│       └── img/
│
└── database/
    └── schema.sql                  ← estrutura completa da BD
```

---

## Instalação no XAMPP

### 1. Copiar o projeto

```
C:\xampp\htdocs\rehablink\
```

### 2. Criar a base de dados

No phpMyAdmin:
1. Criar base de dados `rehablink` com collation `utf8mb4_unicode_ci`
2. Importar `database/schema.sql`
3. Criar utilizador admin com password real:

```php
// executar uma vez (pode ser em script temporário):
echo password_hash('a_tua_password', PASSWORD_BCRYPT, ['cost' => 12]);
// copiar o resultado e inserir na BD:
// UPDATE utilizadores SET password_hash = 'resultado' WHERE email = 'admin@rehablink.pt';
```

### 3. Configurar ligação

Em `config/database.php`, ajustar se necessário:
```php
define('DB_USER', 'root');
define('DB_PASS', '');      // XAMPP: vazio por padrão
define('DB_NAME', 'rehablink');
```

### 4. Verificar APP_URL

Em `config/app.php`:
```php
define('APP_URL', 'http://localhost/rehablink');
```

### 5. Ativar mod_rewrite no XAMPP

Em `C:\xampp\apache\conf\httpd.conf`, garantir que está descomentado:
```
LoadModule rewrite_module modules/mod_rewrite.so
```

E no bloco `<Directory "...htdocs">`:
```
AllowOverride All
```

---

## Padrão de Página PHP (como construir as restantes)

Todas as páginas seguem este template:

```php
<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

$pagina_titulo = 'Título da Página';
$pagina_ativa  = 'chave_menu';       // ex: 'sessoes', 'pacientes'
// Opcional:
$css_extra = [APP_URL . '/public/assets/datatables/...'];
$js_head   = ['https://cdn.jsdelivr.net/npm/chart.js'];

require_once __DIR__ . '/../../includes/header_PERFIL.php';
require_once __DIR__ . '/../../includes/sidebar_PERFIL.php';

// --- lógica PHP / queries BD ---
$db = getDB();
// ...

?>
        <main class="content">
            <!-- HTML da página -->
        </main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
```

---

## Tabelas da Base de Dados

| Tabela            | Descrição                                        |
|-------------------|--------------------------------------------------|
| `utilizadores`    | Base de todos os perfis (admin/médico/técnico/utente) |
| `profissionais`   | Dados extra de médicos e técnicos                |
| `utentes`         | Dados clínicos dos pacientes                     |
| `dispositivos`    | Dispositivos EMG associados a utentes            |
| `sessoes`         | Sessões de treino/reabilitação                   |
| `leituras_emg`    | Dados brutos EMG por sessão                      |
| `metricas_sessao` | Métricas calculadas (RMS, MAV, frequência, score)|
| `prescricoes`     | Prescrições médicas                              |
| `consultas`       | Consultas médicas agendadas                      |
| `mensagens`       | Mensagens internas entre utilizadores            |
| `faturas`         | Faturação por sessão/utente                      |
| `logs_acesso`     | Auditoria de logins e ações (segurança)          |

---

## Problemas do Projeto Original — O Que Foi Corrigido

| Problema Original | Solução Implementada |
|---|---|
| Toda a lógica em localStorage (dados perdidos ao limpar browser) | BD MySQL persistente |
| Sem autenticação real — URLs acessíveis diretamente | `requireLogin()` / `requirePerfil()` em cada página |
| Header/sidebar repetido em cada ficheiro (40+ vezes) | Partials PHP reutilizáveis por perfil |
| Dados hardcoded (nomes, datas) | Queries PDO com dados reais da BD |
| Sem separação frontend/backend/dados | Estrutura `config/`, `includes/`, `api/`, `private/` |
| Links entre páginas com caminhos relativos frágeis | `APP_URL` constante usada em todo o lado |
| Sem proteção XSS | Função `h()` em todos os outputs |
| Sem controlo de sessão | Sessão segura com `session_regenerate_id()` |
| Sem logs de acesso | Tabela `logs_acesso` preenchida em login/logout |

---

## Próximos Passos Recomendados (por prioridade)

1. Criar `editar_utilizador.php` (padrão idêntico ao `novo_utilizador.php`)
2. Converter `gestao_PS.php`, `lista_dispositivos.php`, `controlo_faturacao.php` (admin CRUD)
3. Converter páginas do médico: `lista_prescricoes.php`, `gestaoUtente.php`
4. Converter páginas do técnico: `perfil_paciente.php`, `iniciar_sessao.php`
5. Implementar API WebSocket bridge para dados EMG em tempo real (já existe `api/sessoes/leituras.php`)
6. Adicionar hash CSRF em todos os formulários POST
7. Considerar migrar assets para CDN ou compilar com Vite quando o projeto crescer
