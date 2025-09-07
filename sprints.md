mandou muito bem! 👏 Seu diagnóstico está consistente e cobre os pontos cruciais. Vou validar o que você escreveu e, em seguida, montar um **plano de melhoria do banco** (sem código) que dá para aplicar em etapas curtas e seguras.

# O que você colocou — faz sentido?

* **Hashes mistos (MD5 vs bcrypt)**: perfeito. Isso é prioridade 0 de segurança. Padronizar em `password_hash/password_verify` e **rehash transparente no login** é o caminho certo.
* **Índices para e-mail/telefone**: procede. Para tabelas “usuário”, e-mail deve ser **único e indexado**; telefone também costuma precisar de índice (dependendo do uso).
* **`ultimo_acesso` com `0000-00-00 …`**: concordo. Ativa **STRICT** e use **NULL** como ausência de valor.
* **`formas_pgto` misturando “checked de UI”**: boa chamada. Isso é detalhe de apresentação; no banco, modele como **boolean/enum** ou, melhor, **tabela relacional** se houver múltiplas formas por usuário/parceiro.
* **Tokens, códigos e fluxos**: UUID v4 para reset é ok; códigos 6 dígitos com **expiração + limite de tentativas** é o padrão esperado.
* **Middleware de autorização frágil**: embora isso seja mais backend do que DB, o reflexo no banco existe (ex.: **tokens inválidos persistidos**, logs gigantes). Sua observação está correta.

# Em que estágio o projeto está

* **DB funcional**, mas com heranças legadas (MD5, datas “zero”, chaves/índices faltando).
* **API responde** e faz SELECTs/prepared statements corretamente.
* **Rotas de produtos/catálogo** operam, porém sem paginação/filtros robustos.
* **Fluxos de conta** existem, mas dependem de e-mail e de consistência de constraints.

Isso é um **ótimo ponto de partida** para hardening.

---

# Plano de ação (focado no BANCO, sem código)

## 1) Política de dados & collation

* **Charset/Collation**: padronizar **utf8mb4** (ex.: `utf8mb4_0900_ai_ci` no MySQL, `utf8mb4_general_ci`/`utf8mb4_unicode_ci` no MariaDB).
  – garante acentuação/emoji correta e busca case-insensitive para e-mail.
* **Timezone**: timestamps em **UTC** no banco; a app converte para a zona do usuário.
  – evite `CURRENT_TIMESTAMP` “escondendo” TZ; registre `created_at/updated_at` em UTC.
* **SQL Mode**: ativar **STRICT\_TRANS\_TABLES**, **ERROR\_FOR\_DIVISION\_BY\_ZERO**, **NO\_ZERO\_DATE/NO\_ZERO\_IN\_DATE**.
  – isso força você a corrigir “zero dates” para **NULL** e previne dados ruins no futuro.

## 2) Normalização mínima & modelagem

* **Usuários (administradores, cadastro\_parceiro, cadastro\_cliente, cadastro\_motoboy):**

  * Unificar atributos comuns (e-mail, telefones, senha, status, ultimo\_acesso, created\_at, updated\_at) com **mesma semântica** e nomes coerentes.
  * Se não dá para unificar tabelas agora, **alinhe as constraints** (mesma unicidade/índices/colunas obrigatórias) em todas.
  * **E-mail único** por tabela (ou global se houver interseção) — depende da regra de negócio (um e-mail pode ser cliente e parceiro?).
* **Chaves & Identidades**:

  * Garanta **PK auto-increment** (ou UUID) em todas.
  * **FKs reais** entre tabelas relacionais (ex.: `catalogo` → `produtos`, `veiculo_*` → `produtos`/`catalogo`).
* **formas\_pgto**:

  * Se for *um conjunto* de métodos por parceiro/cliente, crie **tabela pivô** (`usuario_forma_pgto`), com colunas tipo `{user_id, forma_pgto_id, ativo}` e um catálogo `forma_pgto`.
  * Se for *um atributo único*, normalize para **enum** (ex.: `PIX`, `CREDITO`, `DEBITO`, …).
* **keys\_login**:

  * Defina a intenção: auditoria (histórico) ou última chave ativa?

    * Se auditoria: **não “regrave”** — mantenha histórico com `created_at` e **index por user\_id + created\_at DESC**.
    * Se “último login”: então **uma linha por usuário** ou **key atual** em tabela separada.
  * Para tabelas de log/auditoria, planeje **retenção** (ver item 8).
* **password\_requests / deactivation\_requests**:

  * Garanta **unicidade por usuário + estado “pendente”** (não ter 10 tokens ativos).
  * Campos: `token` (UUID), `expires_at`, `attempts`, `status` (PENDING/USED/EXPIRED), `used_at`.
  * Índices: por `token` e por `(user_id, status)`.
* **Produtos/Catálogo/Veículo**:

  * Confirme se `catalogo` é **catálogo de códigos comerciais** e `produtos` é **instância/estoque/oferta**.
  * `veiculo_*` deve referenciar **PK** (FKs) e ter chaves **compostas** quando for ponte (ex.: `produto_id + atributo`).

## 3) Integridade referencial (FKs) e on-delete/update

* Adicionar **FKs** nas relações óbvias com **ON DELETE** apropriado:

  * cascata quando for “filho” dependente (ex.: `produto_variacao` morre se `produto` morre),
  * `RESTRICT`/`NO ACTION` se não quiser deletar pai com filho,
  * `SET NULL` quando a referência é opcional.
* Antes de ativar FKs, rode **pré-checagens** (queries de órfãos) e **corrija** dados inconsistentes.

## 4) Chaves únicas e índices

* **Únicos**: e-mail, documento (CPF/CNPJ) — conforme regra de negócio.
* **Índices**:

  * `(email)` em todas as tabelas de usuário.
  * Se busca por telefone é comum: `(telefone)` (ou `(ddd,numero)` se separados).
  * `password_requests(token)`, `password_requests(user_id, status)`
  * Em produtos/catálogo:

    * `(codigo)` se busca por código é frequente.
    * `(categoria, marca, modelo)` se compõe filtros.
  * **Data de acesso/created\_at** para ordenações frequentes (DESC).

## 5) Dados “zero”, nulos e defaults

* Migrar `0000-00-00 00:00:00` → **NULL**.
* Definir **defaults sensatos**:

  * `created_at` default CURRENT\_TIMESTAMP (opcional) **ou** preencher via aplicação.
  * `updated_at` atualizado pela aplicação; evitar triggers se possível (mais previsível).
* Campos booleanos **TINYINT(1)** ou **BOOLEAN** com default **0/false** quando fizer sentido.

## 6) Segurança de credenciais e PII

* **Senhas**: padronizar **bcrypt (ou Argon2id)**; manter “pepper” opcional no app (não no DB).
* **E-mails/códigos**: **não logar** tokens/códigos inteiros; se preciso, mascarar no log (últimos 4 chars).
* **PII** (CPF, telefone): garantir **criptografia em repouso** é desejável (app-level), ao menos para dumps/exports; no DB, restrinja acesso por usuário e view.
* **JWT**: não salvar JWT em tabela “as is”. Se precisar de revogação, guarde **JTIs**/lista negra com expiração curta.

## 7) Qualidade de dados

* **Validações de formato** (nivel app): e-mail, CPF/CNPJ, CEP, telefone.
* **Normalização**: e-mail *lowercase* na persistência; telefone somente dígitos; CEP numérico.
* **Conflitos de duplicidade**: antes de impor UNIQUE, **deduplicar** (merge/cleanup).

## 8) Logs e retenção

* **keys\_login** e afins: defina **retenção** (ex.: 90 dias).
* Se volume crescer, considerar:

  * **particionamento por data** (quando fizer sentido), ou
  * **tabelas de arquivo** (ex.: `_archive`) com job mensal de migração.

## 9) Performance e consultas

* **Paginação** padrão em endpoints de lista (LIMIT/OFFSET ou keyset pagination).
* **Filtros** coerentes → crie índices **compostos** conforme padrões de busca reais (ex.: `categoria, marca, modelo`).
* **Explain** nas consultas críticas para validar que índices estão sendo usados.
* Avaliar cache (Redis) para catálogos menos mutáveis.

## 10) Governança & migrações

* **Migrações idempotentes e reversíveis** (up/down) para todos os passos acima.
* **Ordem segura**:

  1. *Pré-checagens* (detectar órfãos/duplicidades/datas inválidas);
  2. *Correção de dados*;
  3. *Criação de colunas novas* (NULLables);
  4. *Backfill*;
  5. *Add índices* (com `ONLINE` quando disponível);
  6. *Add FKs/UNIQUEs*;
  7. *Tornar colunas NOT NULL* quando for o caso.
* **Janelas de mudança pequenas**, métricas antes/depois e plano de rollback (snapshot/backup do schema/dados).

## 11) Observabilidade do banco

* **Slow query log** habilitado em ambiente de teste para capturar gargalos.
* Métricas básicas: conexões ativas, buffer pool, deadlocks, I/O.

## 12) Testes de aceitação (DB-first)

Defina critérios “passa/falha” para cada etapa:

* Usuários: `email` único por tabela; insert duplicado **falha** com mensagem clara.
* Login: `password_verify` aceita registros antigos (rehash dispara e **atualiza**).
* Password reset: 1 token PENDING por usuário; token expirado **não autentica**.
* Produtos: `buscar-catalogo/{codigo}` retorna em `<X ms>` com índice.
* Zero dates: nenhum `0000-00-00` após migração; STRICT ativo.

---

## Roadmap sugerido (em 4 sprints curtinhas)

**Sprint 1 — Saneamento & base**

* Charset/collation padronizados, SQL\_MODE estrito.
* Remover “zero dates” → NULL.
* Criar `created_at/updated_at` onde faltar.
* Índices/UNIQUE de e-mail e documentos (com pré-deduplicação).

**Sprint 2 — Segurança & fluxos críticos**

* Padronizar hash de senha (bcrypt/Argon2id).
* Rehash transparente no login.
* Password reset: unicidade de token por usuário, expiração, attempts.
* Não logar PII/tokens; revisar políticas de log.

**Sprint 3 — Integridade & modelagem**

* FKs principais (usuário→filhos; produtos→catálogo/veículo).
* Redesenhar `formas_pgto` (enum ou pivô).
* `keys_login`: decidir se histórico ou “último login”; ajustar modelo/índices.

**Sprint 4 — Performance & governança**

* Paginação e índices compostos (produtos/catálogo).
* Retenção/particionamento de logs.
* Testes de carga leves + slow query log.
* Documentar migrações, rollback e rotinas operacionais.

---

se quiser, eu pego uma amostra do seu `autopop.sql` (sem dados pessoais) e te devolvo um **checklist com os índices/FKs específicos por tabela** — aí você já aplica como migração em cima do que tem hoje.
