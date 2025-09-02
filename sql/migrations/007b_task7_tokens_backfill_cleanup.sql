USE autopop;

UPDATE password_requests
   SET expires_at = datahora_requisicao + INTERVAL 60 MINUTE
 WHERE expires_at IS NULL;

UPDATE deactivation_requests
   SET expires_at = datahora_requisicao + INTERVAL 15 MINUTE
 WHERE expires_at IS NULL;

DELETE FROM password_requests
 WHERE usado = 1 OR (expires_at IS NOT NULL AND expires_at < NOW());

DELETE FROM deactivation_requests
 WHERE usado = 1 OR (expires_at IS NOT NULL AND expires_at < NOW());

SELECT 'password_requests_exp_null' AS check_name, COUNT(*) AS qtd
  FROM password_requests WHERE expires_at IS NULL;

SELECT 'deactivation_requests_exp_null' AS check_name, COUNT(*) AS qtd
  FROM deactivation_requests WHERE expires_at IS NULL;

SELECT 'password_requests_expired_restantes' AS check_name, COUNT(*) AS qtd
  FROM password_requests WHERE expires_at < NOW();

SELECT 'deactivation_requests_expired_restantes' AS check_name, COUNT(*) AS qtd
  FROM deactivation_requests WHERE expires_at < NOW();
