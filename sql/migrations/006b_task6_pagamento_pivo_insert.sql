USE autopop;

INSERT IGNORE INTO produto_forma_pgto (produto_codigo, forma_codigo)
SELECT
  p.codigo AS produto_codigo,
  jt.forma_codigo
FROM produtos p
JOIN JSON_TABLE(
       CONCAT('[', REPLACE(COALESCE(p.formas_pgto, ''), ' ', ''), ']'),
       '$[*]' COLUMNS (forma_codigo INT PATH '$')
     ) AS jt
  ON p.formas_pgto IS NOT NULL AND p.formas_pgto <> '';
