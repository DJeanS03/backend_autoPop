USE autopop;

ALTER TABLE produtos
  ADD CONSTRAINT fk_prod_parceiro
    FOREIGN KEY (cod_parceiro)
    REFERENCES cadastro_parceiro(codigo)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE produtos
  ADD CONSTRAINT fk_prod_catalogo
    FOREIGN KEY (cod_catalogo)
    REFERENCES catalogo(codigo)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE produtos
  ADD CONSTRAINT fk_prod_catveic
    FOREIGN KEY (cod_categoria_veiculo)
    REFERENCES veiculo_categoria(codigo)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE produtos
  ADD CONSTRAINT fk_prod_marcaveic
    FOREIGN KEY (cod_marca_veiculo)
    REFERENCES veiculo_marca(codigo)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE produtos
  ADD CONSTRAINT fk_prod_modveic
    FOREIGN KEY (cod_modelo_veiculo)
    REFERENCES veiculo_modelo(codigo)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE catalogo
  ADD CONSTRAINT fk_cat_catveic
    FOREIGN KEY (cod_categoria_veiculo)
    REFERENCES veiculo_categoria(codigo)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE catalogo
  ADD CONSTRAINT fk_cat_marcaveic
    FOREIGN KEY (cod_marca_veiculo)
    REFERENCES veiculo_marca(codigo)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE catalogo
  ADD CONSTRAINT fk_cat_modveic
    FOREIGN KEY (cod_modelo_veiculo)
    REFERENCES veiculo_modelo(codigo)
    ON UPDATE CASCADE ON DELETE RESTRICT;

SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA='autopop' AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, COLUMN_NAME;
