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
require_once __DIR__."/../../templates/emailDeactivatePartner.php";

$app->post('/deactivationrequest-partner', function(Request $request, Response $response, $args){
  $decoded = JWT::decode($request->getHeaderLine('Authorization'), new Key($this->get('secret'), $this->get('crypt-type')));

  try{
    $dadosUsuario = $this->get('db')->prepare("SELECT codigo, email, senha FROM cadastro_parceiro WHERE codigo = :id");
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
  $emailBody = emailDeactivatePartner($data_email);
  emailSend('contato@autopop.com.br', 'contato@autopop.com.br', $decoded->email, 'Requisição de desativação de conta de parceiro - Autopop', $emailBody);

  $response->getBody()->write(json_encode(['msg' => 'Desativação de conta requisitada. Verifique seu email para preencher o código requisitado']));
  return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});

$app->post('/deactivateaccount-verificationcode-partner', function(Request $request, Response $response, $args){
  $input = $request->getParsedBody();
  $decoded = JWT::decode($request->getHeaderLine('Authorization'), new Key($this->get('secret'), $this->get('crypt-type')));

  if(empty($input['code']) || empty($input['senha'])){
    $response->getBody()->write(json_encode(['msg' => 'Campo(s) obrigatório(s) não preenchido(s)']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  try{
    $dadosUsuario = $this->get('db')->prepare("SELECT codigo, email, senha FROM cadastro_parceiro WHERE codigo = :id");
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
                                                    AND tipo = 'p'
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

$app->delete('/deactivateaccount-partner/{id}', function(Request $request, Response $response, $args){
  $id = $args['id'];
  $decoded = JWT::decode($request->getHeaderLine('Authorization'), new Key($this->get('secret'), $this->get('crypt-type')));

  try{
    $deactivateAccount = $this->get('db')->prepare("UPDATE cadastro_parceiro SET inativo = 1 WHERE codigo = :id");
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
  emailSend('contato@autopop.com.br', 'contato@autopop.com.br', $decoded->email, "Conta desativada - Parceiro: Autopop", $emailBody);

  $response->getBody()->write(json_encode(['msg' => 'Usuário parceiro desativado com sucesso. Para reativá-lo, entre em contato com o suporte']));
  return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

// GET /me-partner (protegido por Bearer)
$app->get('/me-partner', function(Request $request, Response $response) {
  $auth = $request->getHeaderLine('Authorization');
  if (!preg_match('/Bearer\s+(.*)$/i', $auth, $m)) {
    $response->getBody()->write(json_encode(['msg' => 'Token ausente']));
    return $response->withHeader('Content-Type','application/json')->withStatus(401);
  }

  try {
    $decoded = \Firebase\JWT\JWT::decode($m[1], new \Firebase\JWT\Key($this->get('secret'), $this->get('crypt-type')));
  } catch (Exception $e) {
    $response->getBody()->write(json_encode(['msg' => 'Token inválido']));
    return $response->withHeader('Content-Type','application/json')->withStatus(401);
  }

  // Busca no cadastro_parceiro pelo ID do token
  $stmt = $this->get('db')->prepare('SELECT codigo as id, nome, email, administrador FROM cadastro_parceiro WHERE codigo = :id LIMIT 1');
  $stmt->bindParam('id', $decoded->id);
  $stmt->execute();

  if ($stmt->rowCount() === 0) {
    $response->getBody()->write(json_encode(['msg' => 'Usuário não encontrado']));
    return $response->withHeader('Content-Type','application/json')->withStatus(404);
  }

  $me = $stmt->fetch(\PDO::FETCH_ASSOC);
  $response->getBody()->write(json_encode(['me' => $me]));
  return $response->withHeader('Content-Type','application/json');
});
