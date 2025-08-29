<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use src\controllers\ClienteController;

// Rota para o cadastro de cliente
$app->post('/cadastro-cliente', ClienteController::class . ':cadastro');
