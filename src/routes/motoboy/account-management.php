<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__."/../../helpers/generateVerificationCode.php";
require_once __DIR__."/../../helpers/emailSend.php";
require_once __DIR__."/../../templates/emailDeactivatedAccount.php";
require_once __DIR__."/../../templates/emailDeactivateMotoboy.php";

$app->post('/deactivationrequest-motoboy', function(Request $request, Response $response, $args){
  $decoded = JWT::decode($request->getHeaderLine('Authorization'), new Key($this->get('secret'), $this->get('crypt-type')));

  try{
    $dadosUsuario = $this->get('db')->prepare("SELECT id, email, senha FROM cadastro_motoboy WHERE id = :id");
    $dadosUsuario->bindParam('id', $decoded->id);
    $dadosUsuario->execute();
  }
  catch(PDOException $e){
    $response
    ->getBody()
    ->write(
      json_encode(
        ['msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: '.$e->getMessage()]
      )
    );

    return $response
          ->withHeader('Content-Type', 'application/json')
          ->withStatus(500);
  }

  if($dadosUsuario->rowCount() === 0){
    $response->getBody()->write(json_encode(['msg' => 'Usuário não encontrado. Entre em contato com o suporte']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  $verifyCode = generateVerificationCode();

  try{
    $insertDeactivationRequest = $this->get('db')->prepare("INSERT INTO deactivation_requests(datahora_requisicao, id_user, tipo, email, token)
                                                          VALUES(NOW(), :id, :tipo, :email, :token)");
    $insertDeactivationRequest->bindParam('id', $decoded->id);
    $insertDeactivationRequest->bindParam('tipo', $decoded->tipo_usuario);
    $insertDeactivationRequest->bindParam('email', $decoded->email);
    $insertDeactivationRequest->bindParam('token', $verifyCode);
    $insertDeactivationRequest->execute();
  }
  catch(PDOException $e){
    $response
    ->getBody()
    ->write(
      json_encode(
        ['msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: '.$e->getMessage()]
      )
    );

    return $response
          ->withHeader('Content-Type', 'application/json')
          ->withStatus(500);
  }

  $data_email = [
    'token' => $verifyCode
  ];
  $emailBody = emailDeactivateMotoboy($data_email);
  emailSend('contato@autopop.com.br', 'contato@autopop.com.br', $decoded->email, 'Requisição de desativação de conta de motoboy - Autopop', $emailBody);

  $response->getBody()->write(json_encode(['msg' => 'Desativação de conta requisitada. Verifique seu email para preencher o código requisitado']));
  return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});

$app->post('/deactivateaccount-verificationcode-motoboy', function(Request $request, Response $response, $args){
  $input = $request->getParsedBody();
  $decoded = JWT::decode($request->getHeaderLine('Authorization'), new Key($this->get('secret'), $this->get('crypt-type')));

  if(empty($input['code']) || empty($input['senha'])){
    $response->getBody()->write(json_encode(['msg' => 'Campo(s) obrigatório(s) não preenchido(s)']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  try{
    $dadosUsuario = $this->get('db')->prepare("SELECT id, email, senha FROM cadastro_motoboy WHERE id = :id");
    $dadosUsuario->bindParam('id', $decoded->id);
    $dadosUsuario->execute();
  }
  catch(PDOException $e){
    $response
    ->getBody()
    ->write(
      json_encode(
        ['msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: '.$e->getMessage()]
      )
    );

    return $response
          ->withHeader('Content-Type', 'application/json')
          ->withStatus(500);
  }

  if($dadosUsuario->rowCount() === 0){
    $response->getBody()->write(json_encode(['msg' => 'Usuário não encontrado. Entre em contato com o suporte']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  $resDadosUsuario = $dadosUsuario->fetchObject();

  if(crypt($input['senha'], $resDadosUsuario->senha) !== $resDadosUsuario->senha){
    $response->getBody()->write(json_encode(['msg' => 'Senha inválida']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
  }

  try{
    $authenticateRequest = $this->get('db')->prepare("SELECT id FROM deactivation_requests 
                                                    WHERE token = :token 
                                                    AND email = :email
                                                    AND usado = 0
                                                    AND datahora_requisicao BETWEEN NOW() - INTERVAL 30 MINUTE AND NOW()
                                                    AND tipo = 'm'
                                                    ORDER BY id DESC LIMIT 1");
    $authenticateRequest->bindParam('token', $input['code']);
    $authenticateRequest->bindParam('email', $decoded->email);
    $authenticateRequest->execute();
  }
  catch(PDOException $e){
    $response
    ->getBody()
    ->write(
      json_encode(
        ['msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: '.$e->getMessage()]
      )
    );

    return $response
          ->withHeader('Content-Type', 'application/json')
          ->withStatus(500);
  }

  if($authenticateRequest->rowCount() === 0){
    $response->getBody()->write(json_encode(['msg' => 'Código inválido. Tente novamente']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
  }

  $resAuthenticationRequest = $authenticateRequest->fetchObject();

  $response->getBody()->write(json_encode(['id' => strval($resAuthenticationRequest->id)]));
  return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

$app->delete('/deactivateaccount-motoboy/{id}', function(Request $request, Response $response, $args){
  $id = $args['id'];
  $decoded = JWT::decode($request->getHeaderLine('Authorization'), new Key($this->get('secret'), $this->get('crypt-type')));

  try{
    $deactivateAccount = $this->get('db')->prepare("UPDATE cadastro_motoboy SET email_confirmado = 0 WHERE id = :id");
    $deactivateAccount->bindParam('id', $decoded->id);
    $deactivateAccount->execute();
  }
  catch(PDOException $e){
    $response
    ->getBody()
    ->write(
      json_encode(
        ['msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: '.$e->getMessage()]
      )
    );

    return $response
          ->withHeader('Content-Type', 'application/json')
          ->withStatus(500);
  }

  try{
    $updateDeactivationRequest = $this->get('db')->prepare("UPDATE deactivation_requests SET usado = 1 WHERE id = :id");
    $updateDeactivationRequest->bindParam("id", $id);
    $updateDeactivationRequest->execute();
  }
  catch(Exception $e){
    $response
    ->getBody()
    ->write(
      json_encode(
        ['msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: '.$e->getMessage()]
      )
    );

    return $response
          ->withHeader('Content-Type', 'application/json')
          ->withStatus(500);
  }

  $emailBody = emailDeactivatedAccount();
  emailSend('contato@autopop.com.br', 'contato@autopop.com.br', $decoded->email, "Conta desativada - Motoboy: Autopop", $emailBody);

  $response->getBody()->write(json_encode(['msg' => 'Usuário Motoboy desativado com sucesso. Para reativá-lo, entre em contato com o suporte']));
  return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});