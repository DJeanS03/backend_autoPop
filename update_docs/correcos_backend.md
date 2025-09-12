perfeito — aqui vai um roteiro resumido (checkpoint por checkpoint) do backend em PHP. a ideia é cada task ser curta, objetiva e testável.

Config & conexão (PDO + .env)

Padronizar bootstrap: carregar .env, criar Database (PDO, exceptions on, ATTR_EMULATE_PREPARES off, charset utf8mb4).

Centralizar credenciais e CORS em config.

Teste: /health retorna ok e SELECT 1.

Auth #1 — login com password_verify() (+ fallback MD5)

Alterar endpoint de login (admin/parceiro/motoboy/cliente): usar password_verify(); se detectar MD5, validar MD5 uma vez e regravar com password_hash() (rehash transparente).

Registrar keys_login conforme nova PK e UNIQUE(chave).

Teste: logar com conta admin; ver senha re-hashada no DB.

Auth #2 — JWT middleware (stateless)

Gerar JWT (HS256) no login; middleware para proteger rotas privadas; refresh opcional.

Guardar sub (id do usuário) e role (tipo_conta) no token.

Teste: rota protegida 401 sem token / 200 com token.

Rate limit & segurança básica

Rate limit por IP (ex.: 60 req/5min) em login e rotas sensíveis.

Segurança: headers (CORS estrito, Content-Security-Policy básica p/ API, X-Content-Type-Options, Referrer-Policy), json_encode com JSON_THROW_ON_ERROR.

Teste: exceder limite → 429.

Produtos — ler formas de pagamento da pivô

Ajustar SELECTs para produto_forma_pgto (remover dependência do CSV produtos.formas_pgto nas respostas).

Inserção/atualização: aceitar array formas_pgto[] e manter pivô em transação (delete+bulk insert).

Teste: criar/editar produto e conferir pivô.

Validações & FK-friendly

Validar cod_* enviados batendo com FKs (catalogo, veiculo_*, cadastro_parceiro); rejeitar valores inexistentes.

Padronizar mensagens de erro (JSON {code,message,details}).

Teste: enviar cod_catalogo inválido → 422.

Paginação, ordenação e filtros consistentes

Padrão ?page=&per_page=&sort=&q= em listagens (produtos, catálogo, parceiros).

texto_buscador/filtros usando índices criados (categoria/marca/modelo/parceiro).

Teste: paginações estáveis (mesma ordenação, mesmos resultados).

Recuperação de senha & desativação — usar expires_at

Geração de token (UUID v4), salvar com expires_at (60/15 min), validar expiração/uso na confirmação.

Invalidar (marcar usado=1) após uso; não aceitar expirado.

Teste: token expirado → 410; token válido → 200 e marca usado.

Auditoria & logs

Log estruturado (JSON) por request/response (correlation id), erros de DB e autenticação.

Registrar keys_login no login/logout e IP/UA (sem PII sensível).

Teste: ver logs com IDs e latência.

Documentação (OpenAPI) + Insomnia

Especificar rotas de auth/produtos/password/deactivation; publicar openapi.yaml.

Atualizar *.rest/Insomnia para bater nos fluxos com JWT e pivot.

Teste: gerar docs e importar no Insomnia.

Camada de serviço & testes básicos

Extrair lógica de endpoints para Services (ex.: ProductService, AuthService), facilitando teste unitário.

Criar testes mínimos (PHPUnit) para login (verify+rehash) e serviço de produtos (pivot).

Teste: vendor/bin/phpunit com 2–3 casos passando.

Endgame — remoção do CSV (opcional, só no final)

Depois que o front estiver consumindo a pivô, deprecar produtos.formas_pgto.

Migração final: ALTER TABLE produtos DROP COLUMN formas_pgto;

Teste: endpoints seguem OK.


# ==================================================================================================

Contexto já concluído (DB) — referência rápida

Task 1 (DB): senha do admin → bcrypt.

Task 2 (DB): ultimo_acesso NULL e utf8mb4_unicode_ci.

Task 3 (DB): uniques (email/cpf/cnpj/tokens) + índices + keys_login com PK id.

Task 4 (DB): FKs em produtos/catalogo/veiculo_*.

Task 6 (DB): normalização de pagamentos → pivô produto_forma_pgto + migração CSV.

Task 7 (DB): expires_at em tokens + limpeza.

Agora, o foco é backend PHP (src/...).

ROADMAP DO BACKEND (PHP) — o que fazer em cada task
Task A1 — Bootstrap de Config & Conexão (PDO + .env + Health)

O que fazer

Criar um bootstrap único (ex.: src/bootstrap.php) que:

carrega .env (host, db, user, pass, CORS, JWT_SECRET);

cria PDO com ERRMODE_EXCEPTION, ATTR_EMULATE_PREPARES=false, SET NAMES utf8mb4;

expõe uma função db() para obter a conexão.

Rotas:

GET /health → {status:"ok"} + SELECT 1.

Teste

Chamar /health e receber 200 com {status:"ok"}.

Task A2 — Auth #1: Login com password_verify() + fallback MD5 + hash na criação

O PROBLEMA QUE VOCÊ REPORTOU ESTÁ AQUI ✅
Sintoma reproduzido por você

POST /precadastro-partner → 409 Parceiro já existe (porque já havia e-mail/cnpj, OK).

Depois POST /auth-partner com "password":"SenhaForte123!" → 401 "Usuário e/ou senha incorreto(s)".

Causa provável

O fluxo de criação (pré-cadastro/cadastro) está salvando a senha como MD5 ou texto puro.

O login atual usa password_verify() (ou outra verificação) que não bate com esse hash → 401.

O que fazer

Na criação/atualização de senha (pré-cadastro, cadastro e reset):

substituir qualquer md5($senha) por:

$hash = password_hash($senha, PASSWORD_BCRYPT); // cost default ok


salvar $hash na coluna senha das tabelas correspondentes (ex.: cadastro_parceiro).

No login (auth-partner, auth-client, etc.):

buscar o registro por e-mail;

se password_verify($password, $row['senha']) → OK;

fallback MD5: se strlen($row['senha'])===32 && ctype_xdigit($row['senha']) e md5($password) === $row['senha']:

rehash transparente:

$novo = password_hash($password, PASSWORD_BCRYPT);
UPDATE ... SET senha = :novo WHERE codigo = :id


aceitar o login.

retornar 401 apenas se ambos falharem.

Registrar keys_login de acordo com a nova PK id e UNIQUE(chave).

Teste

POST /precadastro-partner com novo e-mail → 201/200.

POST /auth-partner com a mesma senha → 200.

Repetir login em parceiro antigo (com MD5) → primeiro login re-hasha, logins seguintes continuam OK.

Task A3 — Auth #2: JWT Middleware (stateless)

O que fazer

No login bem-sucedido, gerar JWT (HS256) com sub (id), role (tipo_conta), exp.

Criar middleware que valida Authorization: Bearer <token> em rotas privadas.

Adicionar rota /me que retorna usuário autenticado.

Teste

Acessar rota protegida sem token → 401.

Com token válido → 200.

Task A4 — Rate Limit & Segurança básica

O que fazer

Rate limit por IP em /auth-* (ex.: 60 req/5min).

Cabeçalhos de segurança (CORS estrito para o front, X-Content-Type-Options, etc.).

Padronizar respostas JSON com {code,message,details}.

Teste

Exceder limite no login → 429.

Task A5 — Produtos: ler/escrever formas de pagamento via pivô

O que fazer

Em GET produtos: agregar formas_pgto a partir de produto_forma_pgto (não do CSV).

Em POST/PUT produtos: aceitar formas_pgto: [1,2,3] e:

transação: DELETE FROM produto_forma_pgto WHERE produto_codigo=? + INSERT em lote.

(Manter a coluna CSV por compatibilidade até trocarmos o front.)

Teste

Criar/editar produto com [1,3]; GET retorna [1,3].

Task A6 — Validações FK-friendly

O que fazer

Validar cod_catalogo, cod_parceiro, cod_categoria_veiculo, cod_marca_veiculo, cod_modelo_veiculo antes do INSERT/UPDATE (existência).

Falhar com 422 e details claros se não existirem.

Teste

Enviar cod_catalogo inválido → 422.

Task A7 — Paginação, Ordenação, Filtros

O que fazer

Padrão ?page=&per_page=&sort=&q= nas listagens.

Usar os índices criados: ix_produtos_*, ix_*_telefone, ix_passreq_email_data, etc.

Teste

Paginação determinística com ORDER BY.

Task A8 — Recuperação de senha / Desativação: usar expires_at

O que fazer

Na geração de token: salvar expires_at = NOW()+INTERVAL 60 MIN (password) / 15 MIN (deactivation).

Na confirmação: rejeitar expirado (410) / usado (409); ao sucesso, marcar usado=1.

Teste

Confirmar token válido → 200 e usado=1.

Confirmar token expirado → 410.

Task A9 — Auditoria & Logs

O que fazer

Log JSON (correlation-id por request), erros de DB, rejeições de login, latência.

Teste

Ver linhas de log com IDs correlacionados.

Task A10 — Documentação OpenAPI + Insomnia

O que fazer

openapi.yaml com auth/produtos/password/deactivation.

Atualizar collections do Insomnia/“.rest” com JWT + exemplos.

Teste

Importar no Insomnia e executar fluxos.

Task A11 — Services & Testes (PHPUnit)

O que fazer

Extrair lógica para AuthService, ProductService.

Testes unitários mínimos: login (verify+rehash), produto (pivot).

Teste

vendor/bin/phpunit passando.

Task A12 — Remoção do CSV (opcional, só no final)

O que fazer

Quando o front já estiver consumindo a pivô:

ALTER TABLE produtos DROP COLUMN formas_pgto;

Teste

GET/POST/PUT continuam OK lendo/escrevendo na pivô.

Observação final sobre o seu erro (repetindo o foco)

O 401 pós-precadastro-partner será resolvido na Task A2 (Auth #1):

hash na criação com password_hash();

login com password_verify() + fallback MD5 com rehash transparente.