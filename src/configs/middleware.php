<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

function verifyRoute($routeSearch)
{
  $allowed_routes = [
    '/auth',
    '/auth-partner',
    '/auth-motoboy',
    '/auth-client',
    '/passwordrecover-requestclient',
    '/confirm-passwordrecovery-client',
    '/resetpassword-client',
    '/passwordrecover-requestmotoboy',
    '/confirm-passwordrecovery-motoboy',
    '/resetpassword-motoboy',
    '/passwordrecover-requestpartner',
    '/confirm-passwordrecovery-partner',
    '/resetpassword-partner',
    '/confirm-account-client',
    '/precadastro-motoboy',
    '/precadastro-partner',
    '/create-client-account',
    '/listar-produtos',
    '/buscar-catalogo',
    '/aprovar-partner', 
    '/precadastros-partner/{id}',
    '/precadastros-partner',
    '/cadastros-partner',
    '/_debug-auth-partner',
    '/cadastros-partner/by-email',
    '/_reset-pass-partner'
  ];

  foreach ($allowed_routes as $route) {
    if (strpos($routeSearch, $route) === false) {
      continue;
    } else {
      return true;
    }
  }

  return false;
}

$beforeMiddleware = function (Request $request, RequestHandler $handler) use ($app) {
  $auth = $request->getHeaderLine('Authorization');

  $uri = $request->getUri();
  $path = $uri->getPath();

  if (!$auth && !verifyRoute($path)) {
    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write($path . ' Não autorizado');

    return $response->withStatus(401);
  }

  return $handler->handle($request);
};

// --- CORS MIDDLEWARE (DEV e PRODUÇÃO) --- //
$app->add(function (\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Server\RequestHandlerInterface $handler) {
  // Pegue do .env se existir (APP_ENV, FRONT_ORIGIN), senão define padrões
  $env = getenv('APP_ENV') ?: 'local'; // local|dev|prod
  $frontOrigin = getenv('FRONT_ORIGIN'); // ex.: https://meufront.com

  // Em dev/local, libera tudo; em prod, libera só o FRONT_ORIGIN
  $allowOrigin = ($env === 'prod' && $frontOrigin) ? $frontOrigin : '*';

  // Trata pré-flight (OPTIONS) ANTES de bater no auth
  if (strtoupper($request->getMethod()) === 'OPTIONS') {
    $response = new \Slim\Psr7\Response(204); // No Content
    return $response
      ->withHeader('Access-Control-Allow-Origin', $allowOrigin)
      ->withHeader('Vary', 'Origin')
      ->withHeader('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization')
      ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
      ->withHeader('Access-Control-Max-Age', '86400');
  }

  // Requisições "normais"
  $response = $handler->handle($request);
  return $response
    ->withHeader('Access-Control-Allow-Origin', $allowOrigin)
    ->withHeader('Vary', 'Origin')
    ->withHeader('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization')
    ->withHeader('Access-Control-Expose-Headers', 'Content-Length, Content-Type')
    ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
});
// --- FIM CORS --- //

$app->add($beforeMiddleware);

$app->add(new \Slim\HttpCache\Cache('public', 86400));
