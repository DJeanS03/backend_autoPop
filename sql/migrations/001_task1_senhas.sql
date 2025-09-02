USE autopop;

SELECT codigo, email, senha FROM administradores;

SELECT codigo, email, senha
FROM administradores
WHERE CHAR_LENGTH(senha)=32 AND senha REGEXP '^[0-9a-f]{32}$';

UPDATE administradores
SET senha = '$2y$12$KKpLn1bjS9eLQ13hFyOMMOE4UVheGi..68eEYCVAa5yRZj21SRmJO'
WHERE email = 'admin@admin.com';

SELECT COUNT(*) AS md5_restantes
FROM administradores
WHERE CHAR_LENGTH(senha)=32 AND senha REGEXP '^[0-9a-f]{32}$';
