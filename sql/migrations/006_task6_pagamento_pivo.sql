USE autopop;

CREATE TABLE IF NOT EXISTS produto_forma_pgto (
  produto_codigo BIGINT NOT NULL,
  forma_codigo   INT    NOT NULL,
  PRIMARY KEY (produto_codigo, forma_codigo),
  CONSTRAINT fk_pfp_produto FOREIGN KEY (produto_codigo)
    REFERENCES produtos(codigo)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_pfp_forma FOREIGN KEY (forma_codigo)
    REFERENCES formas_pgto(codigo)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

WITH RECURSIVE splitter AS (
  SELECT
    p.codigo AS produto_codigo,
    TRIM(BOTH ',' FROM COALESCE(p.formas_pgto, '')) AS rest,
    CAST(NULL AS CHAR(10)) AS token
  FROM produtos p

  UNION ALL

  SELECT
    produto_codigo,
    CASE
      WHEN LOCATE(',', rest) = 0 THEN ''
      ELSE SUBSTRING(rest, LOCATE(',', rest) + 1)
    END AS rest,
    TRIM(SUBSTRING_INDEX(rest, ',', 1)) AS token
  FROM splitter
  WHERE rest <> ''
)
INSERT IGNORE INTO produto_forma_pgto (produto_codigo, forma_codigo)
SELECT produto_codigo, CAST(token AS UNSIGNED)
FROM splitter
WHERE token IS NOT NULL AND token <> '';

SELECT 'count_produto_forma_pgto' AS check_name, COUNT(*) AS qtd FROM produto_forma_pgto;

SELECT p.codigo,
       p.formas_pgto AS csv,
       GROUP_CONCAT(pfp.forma_codigo ORDER BY pfp.forma_codigo) AS normalizado
FROM produtos p
LEFT JOIN produto_forma_pgto pfp ON pfp.produto_codigo = p.codigo
GROUP BY p.codigo
ORDER BY p.codigo
LIMIT 10;
