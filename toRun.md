ótima pergunta — bora deixar “portável” pra sua outra máquina sem dor de cabeça. A ideia é você **commitar os arquivos certos** e ter **um único script** que roda igual no seu notebook e no outro PC.

Abaixo te dou **duas rotas** (escolhe 1):

* **Rota A (sem Docker):** MySQL/MariaDB instalados na máquina.
* **Rota B (com Docker):** usa `docker compose up` e roda as migrações no container.

Também te deixo um **script PowerShell** que funciona nas duas (sem usar `<`, que quebra no PowerShell).

---

# O que precisa estar versionado (no seu repositório)

* `sql/autopop.sql` (dump base)
* `sql/migrations/` com todos os `.sql` da Sprint 1:

  * `001_sqlmode_charset.sql`
  * `002_zero_dates_to_null.sql`
  * `003_add_timestamps.sql`
  * `004a_precheck_duplicates.sql`
  * `004b_apply_unique_indexes.sql`
  * `004c_apply_common_indexes.sql`
  * `005_register_migrations.sql`
* Código do backend
* `.env.example` (NÃO commitar `.env` com senhas reais)

> No novo PC, você vai criar o `.env` a partir do `.env.example`.

---

# Rota A — Sem Docker (MySQL/MariaDB locais)

## 1) Preparar o ambiente na nova máquina

1. Instalar **PHP 8.1+** e **Composer**.
2. Instalar **MySQL/MariaDB** (ou usar o que já tiver).
3. Clonar o projeto.
4. Criar e preencher `.env` (baseado no seu `.env.example`):

   ```env
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_USER=admin
   DB_PSWD=secret
   DB_NAME=crazy907_autopop

   SECRET_KEY=troque_isto
   CRYPT_JWT=HS256
   MAIL_HOST=...
   MAIL_USER=...
   MAIL_PSWD=...
   MAIL_PORT=...
   FRONT_ORIGIN=http://localhost:3000
   APP_ENV=local
   ```
5. `composer install`
6. (Opcional) `php -S localhost:8080 -t src/public` para subir a API depois.

## 2) Importar o dump base + rodar migrações (em qualquer PC)

### O jeito mais simples (um único script PowerShell)

Crie **na raiz do projeto** o arquivo `run_migrations.ps1` com este conteúdo:

```powershell
# ============================
# run_migrations.ps1
# ============================
# Configure aqui se necessário:
$DB_HOST = $env:DB_HOST; if (-not $DB_HOST) { $DB_HOST = "127.0.0.1" }
$DB_PORT = $env:DB_PORT; if (-not $DB_PORT) { $DB_PORT = 3306 }
$DB_USER = $env:DB_USER; if (-not $DB_USER) { $DB_USER = "admin" }
$DB_PSWD = $env:DB_PSWD; if (-not $DB_PSWD) { $DB_PSWD = "secret" }
$DB_NAME = $env:DB_NAME; if (-not $DB_NAME) { $DB_NAME = "crazy907_autopop" }

# Caminho do mysql.exe (se não estiver no PATH, ajuste aqui)
$mysqlExe = "mysql"
try {
  $null = & $mysqlExe --version 2>$null
} catch {
  # tente caminhos comuns
  $candidatos = @(
    "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe",
    "C:\Program Files\MariaDB 10.6\bin\mysql.exe",
    "C:\xampp\mysql\bin\mysql.exe",
    "C:\wamp64\bin\mysql\mysql8.0.31\bin\mysql.exe"
  )
  foreach ($c in $candidatos) {
    if (Test-Path $c) { $mysqlExe = $c; break }
  }
}

function Run-SqlFile {
  param([string]$filePath)

  if (-not (Test-Path $filePath)) {
    Write-Host "Arquivo não encontrado: $filePath" -ForegroundColor Red
    exit 1
  }

  Write-Host ">> Executando: $filePath" -ForegroundColor Cyan
  $sql = Get-Content -Raw $filePath
  $cmd = & $mysqlExe -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PSWD $DB_NAME --default-character-set=utf8mb4 --ssl-mode=DISABLED 2>&1
  if ($LASTEXITCODE -ne 0) {
    Write-Host "Erro ao abrir mysql (verifique caminho/credenciais)." -ForegroundColor Red
    Write-Host $cmd
    exit 1
  }
  # Pipe do conteúdo para o mysql
  $sql | & $mysqlExe -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PSWD $DB_NAME --default-character-set=utf8mb4 --ssl-mode=DISABLED
  if ($LASTEXITCODE -ne 0) {
    Write-Host "Falha ao aplicar: $filePath" -ForegroundColor Red
    exit 1
  }
}

# 1) Dump base (se ainda não importou)
# Se a base já existe/populada, pode pular; senão, descomente:
# Run-SqlFile "sql\autopop.sql"

# 2) Migrações Sprint 1
Run-SqlFile "sql\migrations\001_sqlmode_charset.sql"
Run-SqlFile "sql\migrations\002_zero_dates_to_null.sql"
Run-SqlFile "sql\migrations\003_add_timestamps.sql"
Run-SqlFile "sql\migrations\004a_precheck_duplicates.sql"
Run-SqlFile "sql\migrations\004b_apply_unique_indexes.sql"
Run-SqlFile "sql\migrations\004c_apply_common_indexes.sql"
Run-SqlFile "sql\migrations\005_register_migrations.sql"

Write-Host "Migrações Sprint 1 concluídas com sucesso." -ForegroundColor Green
```

> Observações:
>
> * Ele **não usa `<`**; funciona no PowerShell em qualquer máquina.
> * Se o MySQL não estiver no PATH, ele tenta localizar em caminhos comuns.
> * Você pode controlar host/porta/usuário/senha/nome do DB via variáveis de ambiente ou editando o topo do script.

**Como rodar:**

```powershell
# Na raiz do projeto
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.\run_migrations.ps1
```

> Se o banco estiver zerado, **descomente** a linha do `autopop.sql` dentro do script (ou importe o `autopop.sql` antes, manualmente com Workbench/VS Code).

### Quer usar `cmd.exe`? (também funciona em qualquer PC)

Na raiz do projeto:

```powershell
# Ajuste usuario/senha/porta conforme necessário
cmd /c "mysql -h 127.0.0.1 -P 3306 -u admin -psecret crazy907_autopop < sql\autopop.sql"
cmd /c "mysql -h 127.0.0.1 -P 3306 -u admin -psecret crazy907_autopop < sql\migrations\001_sqlmode_charset.sql"
cmd /c "mysql -h 127.0.0.1 -P 3306 -u admin -psecret crazy907_autopop < sql\migrations\002_zero_dates_to_null.sql"
cmd /c "mysql -h 127.0.0.1 -P 3306 -u admin -psecret crazy907_autopop < sql\migrations\003_add_timestamps.sql"
cmd /c "mysql -h 127.0.0.1 -P 3306 -u admin -psecret crazy907_autopop < sql\migrations\004a_precheck_duplicates.sql"
cmd /c "mysql -h 127.0.0.1 -P 3306 -u admin -psecret crazy907_autopop < sql\migrations\004b_apply_unique_indexes.sql"
cmd /c "mysql -h 127.0.0.1 -P 3306 -u admin -psecret crazy907_autopop < sql\migrations\004c_apply_common_indexes.sql"
cmd /c "mysql -h 127.0.0.1 -P 3306 -u admin -psecret crazy907_autopop < sql\migrations\005_register_migrations.sql"
```

---

# Rota B — Com Docker

Na nova máquina:

```powershell
docker compose up -d
```

Assumindo que seu serviço se chama `db` (confirme com `docker ps`). Para importar/migrar:

```powershell
# Importar dump base (se necessário)
Get-Content -Raw "sql\autopop.sql" | docker exec -i db mysql -uadmin -psecret crazy907_autopop

# Migrações Sprint 1
Get-Content -Raw "sql\migrations\001_sqlmode_charset.sql" | docker exec -i db mysql -uadmin -psecret crazy907_autopop
Get-Content -Raw "sql\migrations\002_zero_dates_to_null.sql" | docker exec -i db mysql -uadmin -psecret crazy907_autopop
Get-Content -Raw "sql\migrations\003_add_timestamps.sql" | docker exec -i db mysql -uadmin -psecret crazy907_autopop
Get-Content -Raw "sql\migrations\004a_precheck_duplicates.sql" | docker exec -i db mysql -uadmin -psecret crazy907_autopop
Get-Content -Raw "sql\migrations\004b_apply_unique_indexes.sql" | docker exec -i db mysql -uadmin -psecret crazy907_autopop
Get-Content -Raw "sql\migrations\004c_apply_common_indexes.sql" | docker exec -i db mysql -uadmin -psecret crazy907_autopop
Get-Content -Raw "sql\migrations\005_register_migrations.sql" | docker exec -i db mysql -uadmin -psecret crazy907_autopop
```

> Se o serviço do banco no `docker-compose.yml` tiver outro nome (ex.: `mysql`), troque `db` por ele.

---

# Como **testar** que deu certo (em qualquer PC)

### Testes SQL (linha de comando)

```powershell
# STRICT na sessão
mysql -h 127.0.0.1 -P 3306 -u admin -psecret -e "SELECT @@SESSION.sql_mode;" crazy907_autopop

# Charset/collation do DB
mysql -h 127.0.0.1 -P 3306 -u admin -psecret -e "SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='crazy907_autopop';"

# Zero-dates removidos
mysql -h 127.0.0.1 -P 3306 -u admin -psecret -e "SELECT 'adm' T, COUNT(*) C FROM administradores WHERE ultimo_acesso='0000-00-00 00:00:00'
UNION ALL SELECT 'cli', COUNT(*) FROM cadastro_cliente WHERE ultimo_acesso='0000-00-00 00:00:00'
UNION ALL SELECT 'par', COUNT(*) FROM cadastro_parceiro WHERE ultimo_acesso='0000-00-00 00:00:00'
UNION ALL SELECT 'mot', COUNT(*) FROM cadastro_motoboy WHERE ultimo_acesso='0000-00-00 00:00:00';" crazy907_autopop

# Timestamps criados
mysql -h 127.0.0.1 -P 3306 -u admin -psecret -e "DESCRIBE administradores;" crazy907_autopop

# Uniques/índices
mysql -h 127.0.0.1 -P 3306 -u admin -psecret -e "SHOW INDEX FROM cadastro_cliente;" crazy907_autopop

# Migrações registradas
mysql -h 127.0.0.1 -P 3306 -u admin -psecret -e "SELECT * FROM schema_migrations ORDER BY applied_at DESC;" crazy907_autopop
```

### Testes REST (VS Code – REST Client)

`tests.rest`:

```http
@base = http://localhost:8080

### 1) API no ar
GET {{base}}/listar-produtos

### 2) Busca otimizada por código (usa índice que criamos)
GET {{base}}/buscar-catalogo/ABC123

### 3) 404 esperado
GET {{base}}/nao-existe
```

---

# Dicas rápidas pro “PC novo”

* Se a base **já existir** com dados: **pule** o `autopop.sql` e rode só as migrações.
* Se a base **não existir**: rode primeiro `autopop.sql`, **depois** as migrações.
* Se o MySQL estiver em outra porta, ajuste `$DB_PORT` no script ou exporte as variáveis:

  ```powershell
  $env:DB_HOST="127.0.0.1"
  $env:DB_PORT="3307"
  $env:DB_USER="admin"
  $env:DB_PSWD="secret"
  $env:DB_NAME="crazy907_autopop"
  .\run_migrations.ps1
  ```
* Se você usa **Workbench** ou extensão do VS Code, também pode abrir cada `.sql` e “Run”. O script é só pra automatizar.

---

Se quiser, eu já te mando uma **versão do `run_migrations.ps1` com log em arquivo** e “skip automático” quando o índice/unique já existe — mas com o que está acima você já consegue chegar no outro PC, rodar tudo, testar e commitar com segurança.
