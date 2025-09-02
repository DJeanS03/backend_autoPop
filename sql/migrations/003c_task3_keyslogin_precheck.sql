-- 003c_task3_keyslogin_precheck.sql
USE autopop;

SELECT chave, COUNT(*) qtd
FROM keys_login
GROUP BY chave
HAVING COUNT(*) > 1;
