USE autopop;

SET @OLD_SQL_MODE := @@SESSION.sql_mode;
SET SESSION sql_mode = REPLACE(REPLACE(@@SESSION.sql_mode, 'NO_ZERO_IN_DATE', ''), 'NO_ZERO_DATE', '');

UPDATE administradores
   SET ultimo_acesso = NULL
 WHERE ultimo_acesso = '0000-00-00 00:00:00';

UPDATE cadastro_parceiro
   SET ultimo_acesso = NULL
 WHERE ultimo_acesso = '0000-00-00 00:00:00';

ALTER TABLE administradores
  MODIFY COLUMN ultimo_acesso DATETIME NULL DEFAULT NULL;

ALTER TABLE cadastro_parceiro
  MODIFY COLUMN ultimo_acesso DATETIME NULL DEFAULT NULL;

SET SESSION sql_mode := @OLD_SQL_MODE;


ALTER DATABASE autopop CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

ALTER TABLE administradores       CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE atividades            CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE cadastro_cliente      CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE cadastro_motoboy      CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE cadastro_parceiro     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE catalogo              CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE deactivation_requests CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE formas_pgto           CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE keys_login            CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE password_requests     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE precadastro_motoboy   CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE precadastro_parceiro  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE produtos              CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE tipo_conta            CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE veiculo_categoria     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE veiculo_marca         CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE veiculo_modelo        CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SELECT 'DB_COLLATION' AS what, DEFAULT_COLLATION_NAME
  FROM information_schema.SCHEMATA
 WHERE SCHEMA_NAME='autopop';

SELECT 'TB_COLLATION' AS what, TABLE_NAME, TABLE_COLLATION
  FROM information_schema.TABLES
 WHERE TABLE_SCHEMA='autopop'
 ORDER BY TABLE_NAME;

SELECT TABLE_NAME, COLUMN_NAME, IS_NULLABLE, COLUMN_DEFAULT
  FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA='autopop' AND COLUMN_NAME='ultimo_acesso';
