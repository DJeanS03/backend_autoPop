<?php

use DI\Container;
use Psr\Container\ContainerInterface;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(realpath(dirname(__FILE__) . '/..')."/../");
$dotenv->load();

$container = new Container();

$container->set('cache', function(){
  return new \Slim\HttpCache\CacheProvider();
});

$container->set('db', function(){
  $pdo = new PDO("mysql:host=".$_ENV['DB_HOST'].";port=".$_ENV['DB_PORT'].";charset=utf8;dbname=".$_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PSWD']);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, $pdo::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

  return $pdo;
});

$container->set('secret', $_ENV['SECRET_KEY']);

$container->set('crypt-type', $_ENV['CRYPT_JWT']);