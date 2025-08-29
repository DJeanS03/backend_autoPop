ALTER TABLE `cadastro_cliente` ADD COLUMN `token_confirmacao` VARCHAR(50) NULL AFTER `email_confirmado`;

CREATE TABLE password_requests( id BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
  datahora_requisicao DATETIME NOT NULL, 
  id_user VARCHAR(255) NOT NULL, 
  tipo ENUM('c', 'm', 'p') NOT NULL COMMENT 'c = cliente; m = motoboy; p = parceiro',
  email VARCHAR(255) NOT NULL,
  token VARCHAR(255) NOT NULL, 
  usado TINYINT DEFAULT 0 COMMENT 'O = false; 1 = true'
);

CREATE TABLE deactivation_requests( id BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
  datahora_requisicao DATETIME NOT NULL, 
  id_user VARCHAR(255) NOT NULL, 
  tipo ENUM('c', 'm', 'p') NOT NULL COMMENT 'c = cliente; m = motoboy; p = parceiro', 
  email VARCHAR(255) NOT NULL,
  token CHAR(6) NOT NULL, 
  usado TINYINT DEFAULT 0 COMMENT 'O = false; 1 = true'
);