USE autopop;

SELECT 'produtos.cod_parceiro órfãos' AS check_name, p.codigo AS produto_codigo, p.cod_parceiro
FROM produtos p
LEFT JOIN cadastro_parceiro cp ON cp.codigo = p.cod_parceiro
WHERE cp.codigo IS NULL AND p.cod_parceiro IS NOT NULL
LIMIT 20;

SELECT 'produtos.cod_catalogo órfãos' AS check_name, p.codigo AS produto_codigo, p.cod_catalogo
FROM produtos p
LEFT JOIN catalogo c ON c.codigo = p.cod_catalogo
WHERE c.codigo IS NULL AND p.cod_catalogo IS NOT NULL
LIMIT 20;

SELECT 'produtos.cod_categoria_veiculo órfãos', p.codigo, p.cod_categoria_veiculo
FROM produtos p
LEFT JOIN veiculo_categoria vc ON vc.codigo = p.cod_categoria_veiculo
WHERE vc.codigo IS NULL AND p.cod_categoria_veiculo IS NOT NULL
LIMIT 20;

SELECT 'produtos.cod_marca_veiculo órfãos', p.codigo, p.cod_marca_veiculo
FROM produtos p
LEFT JOIN veiculo_marca vm ON vm.codigo = p.cod_marca_veiculo
WHERE vm.codigo IS NULL AND p.cod_marca_veiculo IS NOT NULL
LIMIT 20;

SELECT 'produtos.cod_modelo_veiculo órfãos', p.codigo, p.cod_modelo_veiculo
FROM produtos p
LEFT JOIN veiculo_modelo vmo ON vmo.codigo = p.cod_modelo_veiculo
WHERE vmo.codigo IS NULL AND p.cod_modelo_veiculo IS NOT NULL
LIMIT 20;

SELECT 'catalogo.cod_categoria_veiculo órfãos', c.codigo, c.cod_categoria_veiculo
FROM catalogo c
LEFT JOIN veiculo_categoria vc ON vc.codigo = c.cod_categoria_veiculo
WHERE vc.codigo IS NULL AND c.cod_categoria_veiculo IS NOT NULL
LIMIT 20;

SELECT 'catalogo.cod_marca_veiculo órfãos', c.codigo, c.cod_marca_veiculo
FROM catalogo c
LEFT JOIN veiculo_marca vm ON vm.codigo = c.cod_marca_veiculo
WHERE vm.codigo IS NULL AND c.cod_marca_veiculo IS NOT NULL
LIMIT 20;

SELECT 'catalogo.cod_modelo_veiculo órfãos', c.codigo, c.cod_modelo_veiculo
FROM catalogo c
LEFT JOIN veiculo_modelo vmo ON vmo.codigo = c.cod_modelo_veiculo
WHERE vmo.codigo IS NULL AND c.cod_modelo_veiculo IS NOT NULL
LIMIT 20;

SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA='autopop'
  AND (TABLE_NAME, COLUMN_NAME) IN (
    ('produtos','cod_parceiro'),
    ('produtos','cod_catalogo'),
    ('produtos','cod_categoria_veiculo'),
    ('produtos','cod_marca_veiculo'),
    ('produtos','cod_modelo_veiculo'),
    ('catalogo','cod_categoria_veiculo'),
    ('catalogo','cod_marca_veiculo'),
    ('catalogo','cod_modelo_veiculo')
  )
ORDER BY TABLE_NAME, COLUMN_NAME;

SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA='autopop'
  AND (TABLE_NAME, COLUMN_NAME) IN (
    ('cadastro_parceiro','codigo'),
    ('catalogo','codigo'),
    ('veiculo_categoria','codigo'),
    ('veiculo_marca','codigo'),
    ('veiculo_modelo','codigo')
  )
ORDER BY TABLE_NAME, COLUMN_NAME;
