USE autopop;

UPDATE produtos SET cod_catalogo = NULL            WHERE cod_catalogo = 0;
UPDATE produtos SET cod_categoria_veiculo = NULL   WHERE cod_categoria_veiculo = 0;
UPDATE produtos SET cod_marca_veiculo = NULL       WHERE cod_marca_veiculo = 0;
UPDATE produtos SET cod_modelo_veiculo = NULL      WHERE cod_modelo_veiculo = 0;

ALTER TABLE produtos
  MODIFY cod_catalogo BIGINT NULL,
  MODIFY cod_categoria_veiculo INT NULL,
  MODIFY cod_marca_veiculo INT NULL,
  MODIFY cod_modelo_veiculo INT NULL;

SELECT 'zeros_restantes_cod_catalogo' AS check_name, COUNT(*) AS qtd
FROM produtos WHERE cod_catalogo = 0;
SELECT 'zeros_restantes_cod_categoria' AS check_name, COUNT(*) AS qtd
FROM produtos WHERE cod_categoria_veiculo = 0;
SELECT 'zeros_restantes_cod_marca' AS check_name, COUNT(*) AS qtd
FROM produtos WHERE cod_marca_veiculo = 0;
SELECT 'zeros_restantes_cod_modelo' AS check_name, COUNT(*) AS qtd
FROM produtos WHERE cod_modelo_veiculo = 0;

SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA='autopop' AND TABLE_NAME='produtos'
  AND COLUMN_NAME IN ('cod_catalogo','cod_categoria_veiculo','cod_marca_veiculo','cod_modelo_veiculo')
ORDER BY COLUMN_NAME;
