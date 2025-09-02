/*
SQLyog Ultimate v11.11 (64 bit)
MySQL - 5.5.5-10.4.32-MariaDB : Database - autopop
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`autopop` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

/*Table structure for table `administradores` */

CREATE TABLE `administradores` (
  `codigo` bigint(20) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `fone` varchar(11) NOT NULL,
  `cep` varchar(8) NOT NULL,
  `logradouro` varchar(255) DEFAULT NULL,
  `numero_endereco` int(11) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(150) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `cpf` varchar(50) NOT NULL,
  `razao_social` varchar(255) DEFAULT NULL,
  `tipo_atividade` int(11) DEFAULT NULL,
  `tipo_conta` int(11) DEFAULT NULL,
  `banco` int(11) DEFAULT NULL,
  `agencia` varchar(50) DEFAULT NULL,
  `conta` varchar(75) DEFAULT NULL,
  `email_confirmado` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1 = true; 0 = false',
  `senha` varchar(255) NOT NULL,
  `administrador` tinyint(4) DEFAULT 1 COMMENT '1 = true; 0 = false',
  `inativo` tinyint(1) NOT NULL DEFAULT 0,
  `ultimo_acesso` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `administradores` */

insert  into `administradores`(`codigo`,`nome`,`email`,`fone`,`cep`,`logradouro`,`numero_endereco`,`complemento`,`bairro`,`cidade`,`estado`,`cpf`,`razao_social`,`tipo_atividade`,`tipo_conta`,`banco`,`agencia`,`conta`,`email_confirmado`,`senha`,`administrador`,`inativo`,`ultimo_acesso`) values (1,'Administrador','admin@admin.com','','',NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,0,'21232f297a57a5a743894a0e4a801fc3',1,0,'2024-12-04 16:11:55');

/*Table structure for table `atividades` */

CREATE TABLE `atividades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `atividades` */

/*Table structure for table `cadastro_cliente` */

CREATE TABLE `cadastro_cliente` (
  `id` varchar(255) NOT NULL,
  `telefone` varchar(11) NOT NULL,
  `cpf` varchar(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `senha` varchar(255) DEFAULT NULL,
  `cep` varchar(8) NOT NULL,
  `logradouro` varchar(255) DEFAULT NULL,
  `numero_endereco` int(11) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `email_confirmado` tinyint(4) DEFAULT 0,
  `token_confirmacao` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `cadastro_cliente` */

insert  into `cadastro_cliente`(`id`,`telefone`,`cpf`,`email`,`senha`,`cep`,`logradouro`,`numero_endereco`,`bairro`,`cidade`,`estado`,`email_confirmado`,`token_confirmacao`) values ('163e1cb5-942f-4e48-bebc-511eb94e9a47','69999780424','02128201250','gustavobento262@gmail.com','$2a$08$Kdft0GD8lFcDVQ5D05ARZ.LgKvvZzu3IaACXTmJcP3yD3yWsPDF3i','76860000',NULL,NULL,NULL,NULL,NULL,1,'5afb7e84969b95e1e9424f99c596a1a24158bc7c32a2c66133');

/*Table structure for table `cadastro_motoboy` */

CREATE TABLE `cadastro_motoboy` (
  `id` varchar(255) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `cpf` varchar(255) NOT NULL,
  `telefone` varchar(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `cep` varchar(8) NOT NULL,
  `estado` char(2) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `logradouro` varchar(255) DEFAULT NULL,
  `numero_endereco` int(11) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `veiculo` enum('m','c','b') NOT NULL,
  `placa` varchar(7) DEFAULT NULL,
  `senha` varchar(255) DEFAULT NULL,
  `como_fazer_entregas` enum('f','a') DEFAULT NULL COMMENT 'f = fixo ; a = hora que quiser',
  `email_confirmado` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1 = true; 0 = false',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `cadastro_motoboy` */

/*Table structure for table `cadastro_parceiro` */

CREATE TABLE `cadastro_parceiro` (
  `codigo` bigint(20) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `fone` varchar(11) NOT NULL,
  `cep` varchar(8) NOT NULL,
  `logradouro` varchar(255) DEFAULT NULL,
  `numero_endereco` int(11) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(150) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `cnpj` varchar(50) NOT NULL,
  `razao_social` varchar(255) DEFAULT NULL,
  `tipo_atividade` int(11) DEFAULT NULL,
  `tipo_conta` int(11) DEFAULT NULL,
  `banco` int(11) DEFAULT NULL,
  `agencia` varchar(50) DEFAULT NULL,
  `conta` varchar(75) DEFAULT NULL,
  `email_confirmado` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1 = true; 0 = false',
  `senha` varchar(255) NOT NULL,
  `administrador` tinyint(4) DEFAULT 1 COMMENT '1 = true; 0 = false',
  `inativo` tinyint(1) NOT NULL DEFAULT 0,
  `ultimo_acesso` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `cadastro_parceiro` */

insert  into `cadastro_parceiro`(`codigo`,`nome`,`email`,`fone`,`cep`,`logradouro`,`numero_endereco`,`complemento`,`bairro`,`cidade`,`estado`,`cnpj`,`razao_social`,`tipo_atividade`,`tipo_conta`,`banco`,`agencia`,`conta`,`email_confirmado`,`senha`,`administrador`,`inativo`,`ultimo_acesso`) values (10,'TESTE','a@a.com','','',NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,0,'0cc175b9c0f1b6a831c399e269772661',1,0,'2024-12-04 16:15:28'),(11,'Lucas São Bernardo Pinheiro','lucas.saobernardo@gmail.com','11998965114','06852430',NULL,NULL,NULL,NULL,NULL,NULL,'11444777000161','Valdisnei',1,1,55,'0001','00030008',1,'$2a$08$f2corFX4stvQDQmq7lBxo.OIkvlBRwbiP3Ddc1H6bb5jO4M/40en2',1,1,'2025-02-18 18:30:55');

/*Table structure for table `catalogo` */

CREATE TABLE `catalogo` (
  `codigo` bigint(20) NOT NULL AUTO_INCREMENT,
  `descricao` varchar(150) NOT NULL DEFAULT '',
  `veiculo` tinyint(1) NOT NULL DEFAULT 0,
  `cod_categoria_veiculo` int(11) NOT NULL DEFAULT 0,
  `cod_marca_veiculo` int(11) NOT NULL DEFAULT 0,
  `cod_modelo_veiculo` int(11) NOT NULL DEFAULT 0,
  `ano_veiculo` varchar(100) NOT NULL,
  `img_upload` varchar(256) NOT NULL DEFAULT '',
  `img_principal` varchar(100) NOT NULL,
  PRIMARY KEY (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=1002 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `catalogo` */

insert  into `catalogo`(`codigo`,`descricao`,`veiculo`,`cod_categoria_veiculo`,`cod_marca_veiculo`,`cod_modelo_veiculo`,`ano_veiculo`,`img_upload`,`img_principal`) values (1001,'Bomba de Água',1,2,1,1,'1994 ao 2000','1078f39e81e6e3bcd384df1164d8ef29','0.png');

/*Table structure for table `deactivation_requests` */

CREATE TABLE `deactivation_requests` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `datahora_requisicao` datetime NOT NULL,
  `id_user` varchar(255) NOT NULL,
  `tipo` enum('c','m','p') NOT NULL COMMENT 'c = cliente; m = motoboy; p = parceiro',
  `email` varchar(255) NOT NULL,
  `token` char(6) NOT NULL,
  `usado` tinyint(4) DEFAULT 0 COMMENT 'O = false; 1 = true',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `deactivation_requests` */

insert  into `deactivation_requests`(`id`,`datahora_requisicao`,`id_user`,`tipo`,`email`,`token`,`usado`) values (1,'2025-02-18 18:30:59','11','p','lucas.saobernardo@gmail.com','453227',1);

/*Table structure for table `formas_pgto` */

CREATE TABLE `formas_pgto` (
  `codigo` int(11) NOT NULL AUTO_INCREMENT,
  `descricao` varchar(255) NOT NULL DEFAULT '',
  `complemento_input` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `formas_pgto` */

insert  into `formas_pgto`(`codigo`,`descricao`,`complemento_input`) values (1,'Pix','checked'),(2,'Boleto',''),(3,'Parcelamento (com juros)',''),(4,'Parcelamento (sem juros)',''),(5,'Dinheiro','checked');

/*Table structure for table `keys_login` */

CREATE TABLE `keys_login` (
  `chave` varchar(8) NOT NULL,
  `cod_usuario` int(11) NOT NULL,
  `cod_sub_usuario` int(11) NOT NULL,
  `data_hora_login` datetime NOT NULL DEFAULT current_timestamp(),
  `autenticado_segunda_etapa` tinyint(1) NOT NULL DEFAULT 0,
  `tipo_login` tinyint(2) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

/*Data for the table `keys_login` */

insert  into `keys_login`(`chave`,`cod_usuario`,`cod_sub_usuario`,`data_hora_login`,`autenticado_segunda_etapa`,`tipo_login`) values ('82225081',1,0,'2024-12-04 16:11:55',0,1),('12524033',10,0,'2024-12-04 16:15:28',0,2);

/*Table structure for table `password_requests` */

CREATE TABLE `password_requests` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `datahora_requisicao` datetime NOT NULL,
  `id_user` varchar(255) NOT NULL,
  `tipo` enum('c','m','p') NOT NULL COMMENT 'c = cliente; m = motoboy; p = parceiro',
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `usado` tinyint(4) DEFAULT 0 COMMENT 'O = false; 1 = true',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `password_requests` */

insert  into `password_requests`(`id`,`datahora_requisicao`,`id_user`,`tipo`,`email`,`token`,`usado`) values (1,'2025-01-23 20:05:22','163e1cb5-942f-4e48-bebc-511eb94e9a47','c','gustavobento262@gmail.com','21eb7f12-0a29-4c00-b49b-0d4ddfeae12e',1),(2,'2025-02-18 18:28:08','11','p','lucas.saobernardo@gmail.com','b57b4637-d36c-4101-8ade-564d17544c4c',0);

/*Table structure for table `precadastro_motoboy` */

CREATE TABLE `precadastro_motoboy` (
  `id` varchar(255) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `cpf` varchar(255) NOT NULL,
  `telefone` varchar(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `cep` varchar(8) NOT NULL,
  `estado` char(2) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `logradouro` varchar(255) DEFAULT NULL,
  `numero_endereco` int(11) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `veiculo` enum('m','c','b') NOT NULL,
  `placa` varchar(7) DEFAULT NULL,
  `senha` varchar(255) DEFAULT NULL,
  `como_fazer_entregas` enum('f','a') DEFAULT NULL COMMENT 'f = fixo ; a = hora que quiser',
  `email_confirmado` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1 = true; 0 = false',
  `contrato_enviado` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `precadastro_motoboy` */

/*Table structure for table `precadastro_parceiro` */

CREATE TABLE `precadastro_parceiro` (
  `id` varchar(255) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `fone` varchar(11) NOT NULL,
  `cep` varchar(8) NOT NULL,
  `logradouro` varchar(255) DEFAULT NULL,
  `numero_endereco` int(11) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(150) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `cnpj` varchar(50) NOT NULL,
  `razao_social` varchar(255) DEFAULT NULL,
  `tipo_atividade` int(11) DEFAULT NULL,
  `tipo_conta` int(11) DEFAULT NULL,
  `banco` int(11) DEFAULT NULL,
  `agencia` varchar(50) DEFAULT NULL,
  `conta` varchar(75) DEFAULT NULL,
  `email_confirmado` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1 = true; 0 = false',
  `senha` varchar(255) NOT NULL,
  `contrato_enviado` tinyint(4) NOT NULL DEFAULT 0,
  `administrador` tinyint(4) DEFAULT 1 COMMENT '1 = true; 0 = false',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `precadastro_parceiro` */

insert  into `precadastro_parceiro`(`id`,`nome`,`email`,`fone`,`cep`,`logradouro`,`numero_endereco`,`complemento`,`bairro`,`cidade`,`estado`,`cnpj`,`razao_social`,`tipo_atividade`,`tipo_conta`,`banco`,`agencia`,`conta`,`email_confirmado`,`senha`,`contrato_enviado`,`administrador`) values ('7be5564c-da53-4b72-bdab-4b3d9f8b3acd','Lucas São Bernardo Pinheiro','lucas.saobernardo@gmail.com','11998965114','06852430',NULL,NULL,NULL,NULL,NULL,NULL,'11444777000161','Valdisnei',1,1,55,'0001','00030008',0,'$2a$08$f2corFX4stvQDQmq7lBxo.OIkvlBRwbiP3Ddc1H6bb5jO4M/40en2',0,1);

/*Table structure for table `produtos` */

CREATE TABLE `produtos` (
  `codigo` bigint(20) NOT NULL AUTO_INCREMENT,
  `codigo_interno` varchar(255) NOT NULL DEFAULT '',
  `cod_parceiro` bigint(20) NOT NULL,
  `cod_catalogo` int(11) NOT NULL DEFAULT 0,
  `descricao` varchar(150) NOT NULL DEFAULT '',
  `veiculo` tinyint(1) NOT NULL DEFAULT 0,
  `cod_categoria_veiculo` int(11) NOT NULL DEFAULT 0,
  `cod_marca_veiculo` int(11) NOT NULL DEFAULT 0,
  `cod_modelo_veiculo` int(11) NOT NULL DEFAULT 0,
  `ano_veiculo` varchar(100) NOT NULL,
  `texto_buscador` varchar(255) NOT NULL DEFAULT '',
  `valor_padrao` decimal(8,2) NOT NULL DEFAULT 0.00,
  `promocao` tinyint(1) NOT NULL DEFAULT 0,
  `valor_final` decimal(8,2) NOT NULL DEFAULT 0.00,
  `formas_pgto` varchar(50) NOT NULL DEFAULT '',
  `tipo_frete` int(11) NOT NULL DEFAULT 0,
  `valor_frete` decimal(8,2) NOT NULL DEFAULT 0.00,
  `img_upload` varchar(256) NOT NULL DEFAULT '',
  `img_principal` varchar(100) NOT NULL,
  `utilizar_foto_catalogo` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `produtos` */

insert  into `produtos`(`codigo`,`codigo_interno`,`cod_parceiro`,`cod_catalogo`,`descricao`,`veiculo`,`cod_categoria_veiculo`,`cod_marca_veiculo`,`cod_modelo_veiculo`,`ano_veiculo`,`texto_buscador`,`valor_padrao`,`promocao`,`valor_final`,`formas_pgto`,`tipo_frete`,`valor_frete`,`img_upload`,`img_principal`,`utilizar_foto_catalogo`) values (1,'',10,0,'Teste',0,0,1,1,'2000','Teste Fiat UNO 2000',500.00,1,450.00,'1',2,0.00,'','',0),(2,'',10,1001,'Teste',1,0,1,1,'2000, 2001','Teste Fiat UNO 2000, 2001',500.00,1,450.00,'1,2,3',1,10.00,'','',1),(3,'',10,0,'Teste',1,0,1,1,'1999','Teste Fiat UNO 1999',10000.00,1,9850.00,'1',3,0.00,'705d81c95817345f57aa236022c3f7b5','0.jpg',0),(4,'',10,0,'teste novo',0,0,0,0,'','teste novo',100.00,0,100.00,'1,2',3,0.00,'337b3810d9bdd4b016835e9acbf8db49','0.jpg',0),(5,'',10,0,'Teste Pneu',1,0,0,0,'2010','Teste Pneu   2010',200.00,1,180.00,'1,2',2,0.00,'98a790971162be97fd239ea58600709b','0.jpg',0),(6,'',10,0,'pneu',1,2,1,1,'2000, 2001','pneu Veículo Leve Fiat UNO 2000, 2001',200.00,1,199.00,'1',3,0.00,'','',0),(7,'789584',10,0,'Teste codigo interno',0,0,0,0,'','Teste codigo interno',500.00,0,500.00,'1',3,0.00,'','',0),(8,'',10,0,'teste formas pgto',0,0,0,0,'','teste formas pgto',10.00,0,10.00,'1,3',3,0.00,'','',0),(9,'',10,0,'Pneu',1,2,1,1,'2000','Pneu Veículo Leve Fiat UNO 2000',250.00,1,240.00,'1,2,3',1,5.00,'2648258e550af1aa8f330a9253b6e09c','0.jpg',0),(10,'',10,0,'Teste',1,2,1,1,'2000','Teste Veículo Leve Fiat UNO 2000',500.00,1,450.00,'1',3,0.00,'488a321921ae5375956bbd3f34654995','0.jpg',0);

/*Table structure for table `tipo_conta` */

CREATE TABLE `tipo_conta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descricao` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `tipo_conta` */

/*Table structure for table `veiculo_categoria` */

CREATE TABLE `veiculo_categoria` (
  `codigo` int(11) NOT NULL AUTO_INCREMENT,
  `descricao` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `veiculo_categoria` */

insert  into `veiculo_categoria`(`codigo`,`descricao`) values (1,'Moto'),(2,'Veículo Leve'),(3,'Veículo Pesado'),(4,'Caminhão'),(5,'Ônibus');

/*Table structure for table `veiculo_marca` */

CREATE TABLE `veiculo_marca` (
  `codigo` int(11) NOT NULL AUTO_INCREMENT,
  `descricao` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `veiculo_marca` */

insert  into `veiculo_marca`(`codigo`,`descricao`) values (1,'Fiat'),(2,'Ford');

/*Table structure for table `veiculo_modelo` */

CREATE TABLE `veiculo_modelo` (
  `codigo` int(11) NOT NULL AUTO_INCREMENT,
  `cod_marca` int(11) NOT NULL,
  `descricao` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `veiculo_modelo` */

insert  into `veiculo_modelo`(`codigo`,`cod_marca`,`descricao`) values (1,1,'UNO'),(2,2,'Fusion'),(3,2,'Ranger');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
