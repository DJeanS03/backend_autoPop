perfeito — vamos no passo a passo, com blocos pequenos, cada um com objetivo, arquivos a tocar, como validar e critério de aceite. **sem código por enquanto**.

---

# Plano em blocos (iterativo e testável)

## Bloco 0 — Checkpoint & baseline

**Objetivo:** garantir que temos um estado conhecido antes de mexer.

* **O que fazer**

  * Criar uma branch de trabalho.
  * Tirar um dump do banco atual.
  * Garantir `.env` funcional (DB/MAIL/SECRET/REDIS se houver).
  * Abrir o Insomnia/REST que você já tem.

* **Validação**

  * Aplicação sobe sem erros.
  * Algumas rotas públicas respondem (ex.: `/auth-*` retornando 400/401 conforme payload).

* **Aceite**

  * Tudo igual ao estado antes das mudanças.

---

## Bloco 1 — Login do Parceiro com verificação + rehash transparente

**Objetivo:** resolver primeiro o 401 do parceiro, **apenas no login de parceiro**.

* **Arquivos a tocar**

  * `src/routes/parceiro/auth.php`
  * (Seu helper já criado) `src/helpers/Password.php`

* **O que alterar (conceitualmente, sem código)**

  * Incluir o helper de senha no arquivo.
  * No ponto onde hoje compara a senha do input com a do banco, usar a função do helper que:

    * tenta `password_verify` (bcrypt);
    * se não bater, tenta legado (MD5/crypt);
    * em caso de match legado, sinaliza “rehash necessário” para atualizar no BD com bcrypt.

* **Validação**

  * Login parceiro existente com hash antigo → responde 200 e registro de senha passa a ficar em bcrypt na tabela.
  * Login parceiro com hash já bcrypt → 200 sem alterar hash (a menos que `needs_rehash` peça).
  * Senha errada → 401.

* **Aceite**

  * Três casos acima comportam-se exatamente assim.
  * Nenhum outro endpoint do parceiro foi alterado neste bloco.

---

## Bloco 2 — Criação/Reset de senha do Parceiro gravando sempre bcrypt

**Objetivo:** garantir que **novas** senhas de parceiro sempre nascem seguras.

* **Arquivos a tocar**

  * `src/routes/parceiro/cadastro.php` (ou `precadastro-*.php`, conforme seu fluxo)
  * `src/routes/parceiro/password-management.php`
  * `src/helpers/Password.php` (já criado — apenas usado)

* **O que alterar**

  * Nos pontos de **criação** e **reset** de senha, substituir qualquer hash legado por hash seguro com o helper (bcrypt).

* **Validação**

  * Criar parceiro novo → coluna `senha` já em bcrypt.
  * Reset de senha de parceiro → nova `senha` em bcrypt.
  * Login logo após reset → 200.

* **Aceite**

  * Nenhuma senha nova do parceiro fica fora de bcrypt.
  * Login continua ok.

---

## Bloco 3 — Replicar para Cliente (login + criação + reset)

**Objetivo:** aplicar o mesmo padrão do parceiro agora para **cliente**.

* **Arquivos a tocar**

  * `src/routes/cliente/auth.php`
  * `src/routes/cliente/cadastro.php`
  * `src/routes/cliente/password-management.php`
  * `src/controllers/ClienteController.php` (há uso de `password_hash` — padronizar para o helper)
  * `src/helpers/Password.php` (usar)

* **O que alterar**

  * Igual ao Bloco 1/2, mas para cliente.

* **Validação**

  * Cliente existente com hash antigo → primeiro login re-hasha e passa.
  * Novos cadastros e resets → sempre bcrypt.
  * Insomnia com os fluxos de cliente passando.

* **Aceite**

  * Paridade com parceiro atingida para cliente.

---

## Bloco 4 — Replicar para Motoboy (login + criação + reset)

**Objetivo:** padronizar motoboy.

* **Arquivos a tocar**

  * `src/routes/motoboy/auth.php`
  * `src/routes/motoboy/cadastro.php` (se existir)
  * `src/routes/motoboy/password-management.php`
  * `src/helpers/Password.php` (usar)

* **Validação & Aceite**

  * Mesmo critério dos blocos anteriores.

---

## Bloco 5 — Administradores (se houver fluxo de login na API)

**Objetivo:** tratar o caso de `administradores` (onde vimos MD5 no dump).

* **Arquivos a tocar**

  * *Somente se existir rota de admin* (ex.: `src/routes/admin/auth.php`).
  * Caso não haja na API atual, planejar atualização por migração/CLI separada quando for oportuno.

* **Validação & Aceite**

  * Se houver login admin, mesmo comportamento de rehash transparente.

---

## Bloco 6 — Middleware JWT centralizado por grupos

**Objetivo:** tirar a fragilidade da lista `allowed_routes` por substring.

* **Arquivos a tocar**

  * `src/middleware/JWTAuthMiddleware.php` (novo)
  * `src/public/index.php` (agrupar rotas por `/cliente`, `/parceiro`, `/motoboy` e aplicar o middleware no grupo)
  * `src/configs/middleware.php` (reduzir a lógica antiga de “tem Authorization?”)

* **Validação**

  * Rotas públicas continuam acessíveis sem token (auth, recovery, etc.).
  * Rotas privadas agora exigem JWT válido (401 sem token).
  * Uma rota nova sob `/cliente` nasce automaticamente protegida.

* **Aceite**

  * Proteção centralizada e previsível, sem dependência de substring.

---

## Bloco 7 — CORS (produção estrito, dev flexível)

**Objetivo:** garantir que só o front permitido converse com a API em produção.

* **Arquivos a tocar**

  * `src/configs/middleware.php` (ou onde está o CORS)
  * `.env.example` para documentar `APP_ENV` e `FRONT_ORIGIN`

* **Validação**

  * Em `APP_ENV=prod`, apenas `FRONT_ORIGIN` recebe `Access-Control-Allow-Origin`.
  * Em dev/local, `Origin` de qualquer origem é aceito (ou `*`).
  * Pré-flight OPTIONS responde 204 com os headers.

* **Aceite**

  * CORS previsível entre ambientes.

---

## Bloco 8 — PHPMailer: tratamento de erro (sem `echo`)

**Objetivo:** não vazar erro de SMTP para o cliente; logar e retornar 500.

* **Arquivos a tocar**

  * `src/helpers/emailSend.php` (deixar exceção subir)
  * Pontos chamadores (rotas de recuperação/criação que enviam e-mail) para capturar e logar.

* **Validação**

  * Forçar falha de SMTP → resposta 500 JSON padronizada e log no Monolog.
  * Sucesso de e-mail permanece ok.

* **Aceite**

  * Nada de `echo` na camada de helper; erros tratados e logados.

---

## Bloco 9 — Rate limiting (login e recovery) com Redis

**Objetivo:** conter brute force.

* **Arquivos a tocar**

  * `src/middleware/RateLimitMiddleware.php` (novo)
  * `src/configs/dependencies.php` (cliente Redis)
  * Aplicar o middleware **somente** em `/auth-*` e `/passwordrecover-*`.

* **Validação**

  * 60 requisições em 5 minutos → 429.
  * Debaixo disso → normal.

* **Aceite**

  * Limiter atuando apenas onde importa.

---

## Bloco 10 — Migrações SQL: índices & defaults seguros

**Objetivo:** performance de busca e compatibilidade com MySQL 8 (modo estrito).

* **Arquivos a tocar**

  * `sql/migrations/` (novo script com: `UNIQUE(email)`; índices `telefone/cpf/cnpj`; `ultimo_acesso` → `NULL`; `password_requests.expires_at` se ainda não existir; ajustes em `keys_login`).

* **Validação**

  * Migrar em staging sem erro.
  * Conferir EXPLAIN de queries de login/busca usando os índices.

* **Aceite**

  * Sem warnings/erros; consultas chave usando índice.

---

## Bloco 11 — Tokens com expiração real (password/deactivation)

**Objetivo:** endurecer fluxos sensíveis.

* **Arquivos a tocar**

  * `src/routes/*/password-management.php`
  * `src/routes/*/account-management.php` (desativação)
  * Tabelas `password_requests`/`deactivation_requests` para checar `expires_at` e marcar `used`.

* **Validação**

  * Token válido → 200 e marca `used`.
  * Token expirado → 410.
  * Token já usado → 409.

* **Aceite**

  * Regras aplicadas consistentemente em cliente/parceiro/motoboy.

---

## Bloco 12 — Produtos: pivô de formas de pagamento + paginação/filtros

**Objetivo:** consolidar domínio de produtos e melhorar listagens.

* **Arquivos a tocar**

  * `src/routes/produtos/produtos.php`
  * `src/routes/produtos/catalogo.php`
  * Migrações se necessário para a pivô (se ainda não 100% usada)
  * Padrão de query params `page/per_page/sort/q`.

* **Validação**

  * GET com paginação determinística.
  * CRUD mantém a pivô consistente.
  * Listagens usam índices criados.

* **Aceite**

  * Mesma resposta funcional com mais robustez e performance.

---

## Onde “incluir” o `Password.php` (sem código)

* **Nos arquivos de LOGIN:**
  `src/routes/parceiro/auth.php`, `src/routes/cliente/auth.php`, `src/routes/motoboy/auth.php`.
  → No topo, junto dos outros `require`/`use`, incluir o helper; no ponto de comparação de senha, usar a verificação + sinal de rehash.

* **Nos arquivos de CRIAÇÃO/RESET DE SENHA:**
  `src/routes/*/cadastro.php`, `src/controllers/ClienteController.php` (onde define senha), `src/routes/*/password-management.php`.
  → No momento de gerar/gravar a senha, usar **sempre** o helper de hash (bcrypt). Nenhuma escrita de senha deve ficar fora do helper.

> A ideia é **não** salpicar lógica de senha pela base; apenas **chamar o helper** nos 2 lugares que lidam com senha: **comparar** (login) e **gravar** (criação/reset).

---

se você der o “ok”, começamos **pelo Bloco 1 (login do parceiro)**: aplicamos as chamadas ao helper apenas nesse arquivo, testamos no Insomnia e confirmamos a regravação transparente no banco. Depois seguimos para o Bloco 2.
