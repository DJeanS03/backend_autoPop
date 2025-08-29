<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__."/../../helpers/saltGenerate.php";
require_once __DIR__."/../../templates/emailPasswordRecoveryClient.php";
require_once __DIR__."/../../templates/emailPasswordChanged.php";
require_once __DIR__."/../../helpers/emailSend.php";

$app->post('/passwordrecover-requestclient', function (Request $request, Response $response, $args){
  $input = $request->getParsedBody();

  if(empty($input['email'])){
    $response->getBody()->write(json_encode(['msg' => 'Campo obrigatório não preenchido']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  if(!filter_var($input['email'], FILTER_VALIDATE_EMAIL)){
    $response->getBody()->write(json_encode(['msg' => 'Email inválido. Tente novamente com um email válido']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  try{
    $getUserInformation = $this->get('db')->prepare("SELECT id FROM cadastro_cliente WHERE email = :email");
    $getUserInformation->bindParam('email', $input['email']);
    $getUserInformation->execute();
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

  //Se não existir, ele manda a mesma resposta de existir. Segurança da informação dos clientes
  if($getUserInformation->rowCount() === 0){
    $response->getBody()->write(json_encode(['msg' => 'Requisição executada com sucesso. Um link de recuperação será enviado para o email descrito, caso ele exista']));
    return $response->withHeader('Content-type', 'application/json')->withStatus(201);
  }

  $resGetUserInformation = $getUserInformation->fetchObject();

  $token = Uuid::uuid4();

  try{
    $insertRecoveryRequest = $this->get('db')->prepare("INSERT INTO password_requests (datahora_requisicao, id_user, tipo, token, email, usado)
                                                      VALUES(NOW(), :iduser, 'c', :token, :email, 0)");
    $insertRecoveryRequest->bindParam('iduser', $resGetUserInformation->id);
    $insertRecoveryRequest->bindParam('token', $token);
    $insertRecoveryRequest->bindParam('email', $input['email']);
    $insertRecoveryRequest->execute();
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
    'token' => $token,
    'base_url' => $_ENV['HOST_ADDRESS']."/confirm-passwordrecovery-client"
  ];
  $emailBody = emailPasswordRecoveryClient($data_email);

  emailSend('contato@autopop.com.br', 'contato@autopop.com.br', $input['email'], 'Recuperação de senha: Cliente - Autopop', $emailBody);

  $response->getBody()->write(json_encode(['msg' => 'Requisição executada com sucesso. Um link de recuperação será enviado para o email descrito, caso ele exista']));
  return $response->withHeader('Content-type', 'application/json')->withStatus(201);
});

$app->get('/confirm-passwordrecovery-client/{token}', function(Request $request, Response $response, $args){
  $token_link = $args['token'];

  try{
    $buscaToken = $this->get('db')->prepare("SELECT id, email, token, id_user FROM password_requests
    WHERE token = :token 
    AND usado = 0 
    AND datahora_requisicao BETWEEN NOW() - INTERVAL 30 MINUTE AND NOW()
    AND tipo = 'c'
    ORDER BY id DESC LIMIT 1");
    $buscaToken->bindParam('token', $token_link);
    $buscaToken->execute();
  }
  catch(PDOException $e){
    $response
    ->getBody()
    ->write(json_encode(['msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: '. $e->getMessage()]));

    return $response
          ->withHeader('Content-Type', 'application/json')
          ->withStatus(500);
  }

  if($buscaToken->rowCount() === 0){
    $response->getBody()->write(json_encode([
      'resp' => false,
      'msg' => 'Link inválido. Tente enviar a requisição de recuperação de senha novamente ou entre em contato com o suporte'
    ]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
  }

  $resBuscaToken = $buscaToken->fetchObject();

  $response->getBody()->write(json_encode([
    'resp' => true,
    'email' => $resBuscaToken->email,
    'id' => $resBuscaToken->id,
    'id_user' => $resBuscaToken->id_user, 
    'token' => $token_link
  ]));
  return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

$app->put('/resetpassword-client/{id}', function(Request $request, Response $response, $args){
  $input = $request->getParsedBody();
  $id = $args['id'];

  if(empty($input['senha']) || empty($input['email']) || empty($id)
  || empty($input['id_user']) || empty($input['token'])){
    $response->getBody()->write(json_encode(['msg' => 'Campo(s) obrigatório(s) não informado(s)']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  try{
    $buscaToken = $this->get('db')->prepare("SELECT id FROM password_requests
    WHERE token = :token 
    AND usado = 0 
    AND datahora_requisicao BETWEEN NOW() - INTERVAL 30 MINUTE AND NOW()
    AND tipo = 'c'
    ORDER BY id DESC LIMIT 1");
    $buscaToken->bindParam('token', $input['token']);
    $buscaToken->execute();
  }
  catch(PDOException $e){
    $response
    ->getBody()
    ->write(json_encode(['msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: '. $e->getMessage()]));

    return $response
          ->withHeader('Content-Type', 'application/json')
          ->withStatus(500);
  }

  if($buscaToken->rowCount() === 0){
    $response->getBody()->write(json_encode(['msg' => 'Link inválido. Tente enviar a requisição de recuperação de senha novamente ou entre em contato com o suporte']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  $resBuscaToken = $buscaToken->fetchObject();

  $salt = generateRandomSalt();
  $password = crypt($input['senha'], '$2a$'.$_ENV['COST'].'$'.$salt.'$');

  try{
    $updatePassword = $this->get('db')->prepare("UPDATE cadastro_cliente SET senha = :senha
                                                WHERE id = :id_usuario");
    $updatePassword->bindParam('senha', $password);
    $updatePassword->bindParam('id_usuario', $input['id_user']);
    $updatePassword->execute();
  }
  catch(PDOException $e){
    $response
    ->getBody()
    ->write(json_encode(['msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: '. $e->getMessage()]));

    return $response
          ->withHeader('Content-Type', 'application/json')
          ->withStatus(500);
  }

  try{
    $updatePasswordRequest = $this->get('db')->prepare("UPDATE password_requests SET usado = 1 WHERE id = :id");
    $updatePasswordRequest->bindParam('id', $id);
    $updatePasswordRequest->execute();
  }
  catch(PDOException $e){
    $response
    ->getBody()
    ->write(json_encode(['msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: '. $e->getMessage()]));

    return $response
          ->withHeader('Content-Type', 'application/json')
          ->withStatus(500);
  }

  $emailBody = emailPasswordChanged();

  emailSend('contato@autopop.com.br', 'contato@autopop.com.br', $input['email'], 'Alteração de senha: Cliente - Autopop', $emailBody);

  $response->getBody()->write(json_encode(['msg' => 'Senha atualizada com sucesso. Tente acessar o aplicativo novamente']));
  return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

$app->put('/change-password-client', function(Request $request, Response $response, $args){
  $input = $request->getParsedBody();
  $decoded = JWT::decode($request->getHeaderLine('Authorization'), new Key($this->get('secret'), $this->get('crypt-type')));

  if(empty($input['senha_atual']) || empty($input['senha_nova'])){
    $response->getBody()->write(json_encode(['msg' => 'Campo(s) obrigatório(s) não preenchido(s)']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  try{
    $checkPassword = $this->get('db')->prepare("SELECT email, id, senha FROM cadastro_cliente WHERE id = :id");
    $checkPassword->bindParam('id', $decoded->id);
    $checkPassword->execute();
  }
  catch(PDOException $e){
    $response
    ->getBody()
    ->write(json_encode(['msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: '. $e->getMessage()]));

    return $response
          ->withHeader('Content-Type', 'application/json')
          ->withStatus(500);
  }

  if($checkPassword->rowCount() === 0){
    $response->getBody()->write(json_encode(['msg' => 'Usuário não encontrado. Entre em contato com o suporte']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
  }

  $resCheckPassword = $checkPassword->fetchObject();

  if(crypt($input['senha_atual'], $resCheckPassword->senha) !== $resCheckPassword->senha){
    $response->getBody()->write(json_encode(['msg' => 'Senha atual inválida']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
  }

  $salt = generateRandomSalt();
  $newPassword = crypt($input['senha_nova'], '$2a$'.$_ENV['COST'].'$'.$salt.'$');

  try{
    $updatePassword = $this->get('db')->prepare('UPDATE cadastro_cliente SEt senha = :senha WHERE id = :id');
    $updatePassword->bindParam('senha', $newPassword);
    $updatePassword->bindParam('id', $decoded->id);
    $updatePassword->execute();
  }
  catch(PDOException $e){
    $response
    ->getBody()
    ->write(json_encode(['msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: '. $e->getMessage()]));

    return $response
          ->withHeader('Content-Type', 'application/json')
          ->withStatus(500);
  }

  $emailBody = emailPasswordChanged();

  emailSend('contato@autopop.com.br', 'contato@autopop.com.br', $resCheckPassword->email, 'Alteração de senha: Cliente - Autopop', $emailBody);

  $response->getBody()->write(json_encode(['msg' => 'Senha alterada com sucesso']));
  return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});