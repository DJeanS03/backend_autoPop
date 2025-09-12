<?php

require_once realpath(dirname(__FILE__) . "/..") . "/../vendor/autoload.php";

use Slim\Factory\AppFactory;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

if (PHP_SAPI === 'cli-server') {
  $url = parse_url($_SERVER['REQUEST_URI']);
  $file = __DIR__ . $url['path'];

  if (is_file($file)) {
    return false;
  }
}

//Usando os containers definidos no script descrito
require_once __DIR__ . "/../configs/dependencies.php";
AppFactory::setContainer($container);

$app = AppFactory::create();
$app->addRoutingMiddleware();
//Parsing para poder receber inputs e converter usando o método getParsedBoy
$app->addBodyParsingMiddleware();

//Definindo Log de erro
$logger = new Logger('error');
$logger->pushHandler(new RotatingFileHandler('error.log'));

$customErrorHandler = function (ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails, bool $logErrors, bool $logErrorDetails) use ($app, $logger) {
  if ($logger) {
    $logger->error($exception->getMessage());
  }

  $payload = ['error' => $exception->getMessage()];

  $response = $app->getResponseFactory()->createResponse();
  $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));

  return $response;
};

$errorMiddleware = $app->addErrorMiddleware(true, true, true, $logger);
$errorMiddleware->setDefaultErrorHandler($customErrorHandler);

require_once __DIR__ . "/../configs/middleware.php";
require_once __DIR__ . "/../routes/index.php";

$app->run();
