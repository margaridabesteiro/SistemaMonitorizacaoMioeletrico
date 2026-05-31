# Guia de Instalação — Sistema Mioeléctrico (XAMPP)

## Pré-requisitos
- XAMPP instalado com **Apache** e **MySQL** a correr

---

## Passo 1 — Copiar o projeto

Copiar toda a pasta do projeto para:
```
C:\xampp\htdocs\sistema_mioeletrico\SistemaMonitorizacaoMioeletrico\
```

A estrutura final deve ficar assim:
```
C:\xampp\htdocs\
└── sistema_mioeletrico\
    └── SistemaMonitorizacaoMioeletrico\
        ├── config\
        │   ├── app.php           ← configuração global
        │   └── database.php      ← ligação à BD
        ├── database\
        │   └── sistema_mioeletrico.sql  ← schema da BD
        ├── setup.php             ← script de instalação automática
        ├── index.php
        ├── index.html
        └── ...
```

---

## Passo 2 — Criar a base de dados (2 opções)

### Opção A — Automática (recomendada) ✅
1. Abrir o browser e ir a:
   ```
   http://localhost/sistema_mioeletrico/SistemaMonitorizacaoMioeletrico/setup.php
   ```
2. O script cria a BD, as tabelas e o utilizador admin automaticamente.
3. **Apagar `setup.php` após a instalação!**

### Opção B — Manual via phpMyAdmin
1. Abrir: `http://localhost/phpmyadmin`
2. Clicar em **"Nova"** (barra esquerda)
3. Nome: `sistema_mioeletrico` | Collation: `utf8mb4_unicode_ci` → **Criar**
4. Clicar na BD `sistema_mioeletrico` → separador **Importar**
5. Escolher o ficheiro `database/sistema_mioeletrico.sql` → **Executar**

---

## Passo 3 — Definir a password do admin

Se usou a **Opção A**, já está feito.

Se usou a **Opção B**, executar no phpMyAdmin (separador SQL):
```sql
USE sistema_mioeletrico;
UPDATE utilizadores
SET password_hash = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'admin@rehablink.pt';
```
> Esta hash corresponde à password `password` (da Laravel). Para definir outra password,
> crie um ficheiro PHP temporário com `echo password_hash('SuaPassword', PASSWORD_BCRYPT);`

---

## Passo 4 — Verificar o ficheiro config/app.php

Confirmar que `APP_URL` aponta para o caminho correto:
```php
define('APP_URL', 'http://localhost/sistema_mioeletrico/SistemaMonitorizacaoMioeletrico');
```

---

## Passo 5 — Ativar mod_rewrite (necessário para .htaccess)

1. Abrir `C:\xampp\apache\conf\httpd.conf`
2. Garantir que esta linha está **descomentada** (sem `#`):
   ```
   LoadModule rewrite_module modules/mod_rewrite.so
   ```
3. No bloco `<Directory "C:/xampp/htdocs">`, confirmar:
   ```
   AllowOverride All
   ```
4. Reiniciar o Apache no XAMPP Control Panel.

---

## Passo 6 — Aceder ao sistema

Abrir no browser:
```
http://localhost/sistema_mioeletrico/SistemaMonitorizacaoMioeletrico/private/login/login.php
```

### Credenciais de demonstração (criadas pelo schema.sql)

| Perfil   | Email                    | Password            |
|----------|--------------------------|---------------------|
| Admin    | admin@rehablink.pt       | (definida no setup) |
| Médico   | medico@rehablink.pt      | (definida no setup) |
| Técnico  | tecnico@rehablink.pt     | (definida no setup) |
| Utente   | utente@rehablink.pt      | (definida no setup) |

> Se usou o setup.php com a password padrão `Admin123!`, todas as contas de demo
> têm essa mesma hash. Mude as passwords após o primeiro login.

---

## Resolução de Problemas

| Problema | Solução |
|----------|---------|
| Página em branco | Ativar `display_errors` em `php.ini` do XAMPP |
| "Falha na ligação à BD" | Verificar se MySQL está ativo no XAMPP; confirmar `DB_NAME = 'sistema_mioeletrico'` em `config/database.php` |
| "404 Not Found" | Verificar se `APP_URL` está correto em `config/app.php` |
| Login não funciona | Executar o `setup.php` para regenerar o hash da password |
| `.htaccess` não funciona | Ativar `mod_rewrite` e `AllowOverride All` (ver Passo 5) |

---

## Estrutura da Base de Dados

| Tabela               | Descrição                                              |
|----------------------|--------------------------------------------------------|
| `utilizadores`       | Todos os utilizadores (admin, médico, técnico, utente) |
| `profissionais`      | Dados extra de médicos e técnicos                      |
| `utentes`            | Dados clínicos dos pacientes                           |
| `dispositivos`       | Dispositivos EMG                                       |
| `sessoes`            | Sessões de treino/reabilitação                         |
| `leituras_emg`       | Dados brutos EMG (µV por canal/timestamp)              |
| `metricas_sessao`    | Métricas calculadas (RMS, MAV, frequência, score)      |
| `prescricoes`        | Prescrições médicas                                    |
| `consultas`          | Consultas médicas agendadas                            |
| `mensagens`          | Mensagens internas entre utilizadores                  |
| `faturas`            | Faturação por sessão/utente                            |
| `logs_acesso`        | Auditoria de logins e acessos                          |
| `backoffice_conteudo`| Textos editáveis da landing page                       |
