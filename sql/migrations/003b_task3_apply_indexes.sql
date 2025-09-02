-- 003b_task3_apply_indexes.sql
USE autopop;

-- ============ UNICIDADE POR EMAIL ============
ALTER TABLE administradores
  ADD UNIQUE KEY ux_admin_email (email);

ALTER TABLE cadastro_cliente
  ADD UNIQUE KEY ux_cliente_email (email);

ALTER TABLE cadastro_parceiro
  ADD UNIQUE KEY ux_parceiro_email (email);

ALTER TABLE cadastro_motoboy
  ADD UNIQUE KEY ux_motoboy_email (email);

-- ============ UNICIDADE POR DOCUMENTO ============
ALTER TABLE cadastro_parceiro
  ADD UNIQUE KEY ux_parceiro_cnpj (cnpj);

ALTER TABLE cadastro_motoboy
  ADD UNIQUE KEY ux_motoboy_cpf (cpf);

ALTER TABLE cadastro_cliente
  ADD UNIQUE KEY ux_cliente_cpf (cpf);

-- ============ TOKENS ============
ALTER TABLE password_requests
  ADD UNIQUE KEY ux_passreq_token (token),
  ADD KEY ix_passreq_email_data (email, datahora_requisicao);

ALTER TABLE deactivation_requests
  ADD UNIQUE KEY ux_deact_token (token),
  ADD KEY ix_deact_email_data (email, datahora_requisicao);

-- ============ PRODUTOS (FILTROS/PAGINAÇÃO) ============
CREATE INDEX ix_produtos_parceiro   ON produtos(cod_parceiro);
CREATE INDEX ix_produtos_texto      ON produtos(texto_buscador);
CREATE INDEX ix_produtos_catalogo   ON produtos(cod_catalogo);
CREATE INDEX ix_produtos_categoria  ON produtos(cod_categoria_veiculo);
CREATE INDEX ix_produtos_marca      ON produtos(cod_marca_veiculo);
CREATE INDEX ix_produtos_modelo     ON produtos(cod_modelo_veiculo);

-- ============ TELEFONES (buscas) ============
CREATE INDEX ix_cliente_telefone ON cadastro_cliente(telefone);
CREATE INDEX ix_motoboy_telefone ON cadastro_motoboy(telefone);

-- ============ KEYS_LOGIN (auditoria) ============
-- Se a tabela ainda não tiver PK/índices, aplique:
-- (1) PK auto-increment
ALTER TABLE keys_login
  ADD COLUMN IF NOT EXISTS id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;

-- (2) Unicidade da chave e índices por usuário
ALTER TABLE keys_login
  ADD UNIQUE KEY IF NOT EXISTS ux_keys_login_chave (chave),
  ADD KEY       IF NOT EXISTS ix_keys_login_usuario (cod_usuario),
  ADD KEY       IF NOT EXISTS ix_keys_login_subusuario (cod_sub_usuario);
