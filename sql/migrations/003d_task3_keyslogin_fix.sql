-- 003d_task3_keyslogin_fix.sql
USE autopop;

-- (1) Adicionar PK auto-increment (coluna id na frente)
ALTER TABLE keys_login
  ADD COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;

-- (2) Unicidade da chave (8 chars)
ALTER TABLE keys_login
  ADD UNIQUE KEY ux_keys_login_chave (chave);

-- (3) Índices por usuário/subusuário
ALTER TABLE keys_login
  ADD KEY ix_keys_login_usuario (cod_usuario),
  ADD KEY ix_keys_login_subusuario (cod_sub_usuario);
