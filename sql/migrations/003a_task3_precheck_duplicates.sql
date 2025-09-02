-- 003a_task3_precheck_duplicates.sql
USE autopop;

-- Emails duplicados
SELECT 'administradores' AS tabela, email, COUNT(*) qtd
  FROM administradores GROUP BY email HAVING COUNT(*) > 1;
SELECT 'cadastro_cliente' AS tabela, email, COUNT(*) qtd
  FROM cadastro_cliente GROUP BY email HAVING COUNT(*) > 1;
SELECT 'cadastro_parceiro' AS tabela, email, COUNT(*) qtd
  FROM cadastro_parceiro GROUP BY email HAVING COUNT(*) > 1;
SELECT 'cadastro_motoboy' AS tabela, email, COUNT(*) qtd
  FROM cadastro_motoboy GROUP BY email HAVING COUNT(*) > 1;

-- Documentos duplicados
SELECT 'cadastro_parceiro' AS tabela, cnpj, COUNT(*) qtd
  FROM cadastro_parceiro GROUP BY cnpj HAVING COUNT(*) > 1;
SELECT 'cadastro_motoboy' AS tabela, cpf, COUNT(*) qtd
  FROM cadastro_motoboy GROUP BY cpf HAVING COUNT(*) > 1;
SELECT 'cadastro_cliente' AS tabela, cpf, COUNT(*) qtd
  FROM cadastro_cliente GROUP BY cpf HAVING COUNT(*) > 1;

-- Tokens duplicados
SELECT 'password_requests' AS tabela, token, COUNT(*) qtd
  FROM password_requests GROUP BY token HAVING COUNT(*) > 1;
SELECT 'deactivation_requests' AS tabela, token, COUNT(*) qtd
  FROM deactivation_requests GROUP BY token HAVING COUNT(*) > 1;
