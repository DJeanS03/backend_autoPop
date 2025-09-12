<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . "/../../helpers/saltGenerate.php";
require_once __DIR__ . "/../../templates/emailPasswordChanged.php";
require_once __DIR__ . "/../../templates/emailPasswordRecoveryPartner.php";
require_once __DIR__ . "/../../helpers/emailSend.php";
require_once __DIR__ . "/../../helpers/Password.php";

$app->post('/passwordrecover-requestpartner', function (Request $request, Response $response, $args) {
  $input = $request->getParsedBody();
  if (!is_array($input)) {
    $raw = (string) $request->getBody();
    $decoded = json_decode($raw, true);
    $input = is_array($decoded) ? $decoded : [];
  }

  if (empty($input['email'])) {
    $response->getBody()->write(json_encode(['msg' => 'Campo obrigatório não preenchido']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    $response->getBody()->write(json_encode(['msg' => 'Email inválido. Tente novamente com um email válido']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  try {
    $getPartnerInformation = $this->get('db')->prepare("SELECT codigo FROM cadastro_parceiro WHERE email = :email");
    $getPartnerInformation->bindParam('email', $input['email']);
    $getPartnerInformation->execute();
  } catch (PDOException $e) {
    $response
      ->getBody()
      ->write(
        json_encode(
          ['msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: ' . $e->getMessage()]
        )
      );

    return $response
      ->withHeader('Content-Type', 'application/json')
      ->withStatus(500);
  }

  if ($getPartnerInformation->rowCount() === 0) {
    $response->getBody()->write(json_encode(['msg' => 'Requisição executada com sucesso. Um link de recuperação será enviado para o email descrito, caso ele exista']));
    return $response->withHeader('Content-type', 'application/json')->withStatus(201);
  }

  $resGetPartnerInformation = $getPartnerInformation->fetchObject();

  $token = Uuid::uuid4();

  try {
    $insertRecoveryRequest = $this->get('db')->prepare("INSERT INTO password_requests(datahora_requisicao, id_user, tipo, token, email, usado)
                                                      VALUES(NOW(), :iduser, 'p', :token, :email, 0)");
    $insertRecoveryRequest->bindParam('iduser', $resGetPartnerInformation->codigo);
    $insertRecoveryRequest->bindParam('token', $token);
    $insertRecoveryRequest->bindParam('email', $input['email']);
    $insertRecoveryRequest->execute();
  } catch (PDOException $e) {
    $response
      ->getBody()
      ->write(
        json_encode(
          ['msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: ' . $e->getMessage()]
        )
      );

    return $response
      ->withHeader('Content-Type', 'application/json')
      ->withStatus(500);
  }

  $data_email = [
    'token' => $token,
    'base_url' => $_ENV['HOST_ADDRESS'] . "/confirm-passwordrecovery-partner"
  ];
  $emailBody = emailPasswordRecoveryPartner($data_email);

  emailSend('contato@autopop.com.br', 'contato@autopop.com.br', $input['email'], 'Recuperação de senha: Parceiro - Autopop', $emailBody);

  $response->getBody()->write(json_encode(['msg' => 'Requisição executada com sucesso. Um link de recuperação será enviado para o email descrito, caso ele exista']));
  return $response->withHeader('Content-type', 'application/json')->withStatus(201);
});

$app->get("/confirm-passwordrecovery-partner/{token}", function (Request $request, Response $response, $args) {
  $tokenLink = $args['token'];

  try {
    $buscaToken = $this->get('db')->prepare("SELECT id, email, token, id_user FROM password_requests
    WHERE token = :token 
    AND usado = 0 
    AND datahora_requisicao BETWEEN NOW() - INTERVAL 30 MINUTE AND NOW()
    AND tipo = 'p'
    ORDER BY id DESC LIMIT 1");
    $buscaToken->bindParam('token', $tokenLink);
    $buscaToken->execute();
  } catch (PDOException $e) {
    $response
      ->getBody()
      ->write(json_encode(['msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: ' . $e->getMessage()]));

    return $response
      ->withHeader('Content-Type', 'application/json')
      ->withStatus(500);
  }

  if ($buscaToken->rowCount() === 0) {
    $response->getBody()->write(json_encode([
      'resp' => false,
      'msg' => 'Link inválido ou expirado. Tente enviar a requisição de recuperação de senha novamente ou entre em contato com o suporte'
    ]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
  }

  $resBuscaToken = $buscaToken->fetchObject();

  $response->getBody()->write(json_encode([
    'resp' => true,
    'email' => $resBuscaToken->email,
    'id' => $resBuscaToken->id,
    'id_user' => $resBuscaToken->id_user,
    'token' => $tokenLink
  ]));
  return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

$app->put('/resetpassword-partner/{id}', function (Request $request, Response $response, $args) {
  $input = $request->getParsedBody();
  if (!is_array($input)) {
    $raw = (string) $request->getBody();
    $decoded = json_decode($raw, true);
    $input = is_array($decoded) ? $decoded : [];
  }
  $id = $args['id'];

  if (
    empty($input['senha']) || empty($input['email']) || empty($id)
    || empty($input['id_user']) || empty($input['token'])
  ) {
    $response->getBody()->write(json_encode(['msg' => 'Campo(s) obrigatório(s) não informado(s)']));
    return $response->withHeader('Content-Type', "application/json")->withStatus(401);
  }

  try {
    $buscaToken = $this->get('db')->prepare("SELECT id, email, token, id_user FROM password_requests
    WHERE token = :token 
    AND usado = 0 
    AND datahora_requisicao BETWEEN NOW() - INTERVAL 30 MINUTE AND NOW()
    AND tipo = 'p'
    ORDER BY id DESC LIMIT 1");
    $buscaToken->bindParam('token', $input['token']);
    $buscaToken->execute();
  } catch (PDOException $e) {
    $response
      ->getBody()
      ->write(json_encode(['msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: ' . $e->getMessage()]));

    return $response
      ->withHeader('Content-Type', 'application/json')
      ->withStatus(500);
  }

  if ($buscaToken->rowCount() === 0) {
    $response->getBody()->write(json_encode(['msg' => 'Link inválido ou expirado. Tente enviar a requisição de recuperação de senha novamente ou entre em contato com o suporte']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  $resBuscaToken = $buscaToken->fetchObject();

  $password = hash_password($input['senha']);

  try {
    $updatePassword = $this->get('db')->prepare("UPDATE cadastro_parceiro SET senha = :senha
                                                WHERE codigo = :id");
    $updatePassword->bindParam('senha', $password);
    $updatePassword->bindParam('id', $input['id_user']);
    $updatePassword->execute();
  } catch (PDOException $e) {
    $response
      ->getBody()
      ->write(json_encode(['msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: ' . $e->getMessage()]));

    return $response
      ->withHeader('Content-Type', 'application/json')
      ->withStatus(500);
  }

  try {
    $updatePasswordRequest = $this->get('db')->prepare("UPDATE password_requests SET usado = 1
                                                      where id = :id");
    $updatePasswordRequest->bindParam('id', $id);
    $updatePasswordRequest->execute();
  } catch (PDOException $e) {
    $response
      ->getBody()
      ->write(json_encode(['msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: ' . $e->getMessage()]));

    return $response
      ->withHeader('Content-Type', 'application/json')
      ->withStatus(500);
  }

  $emailBody = emailPasswordChanged();

  emailSend('contato@autopop.com.br', 'contato@autopop.com.br', $input['email'], 'Alteração de senha: Parceiro - Autopop', $emailBody);

  $response->getBody()->write(json_encode(['msg' => 'Senha atualizada com sucesso. Tente acessar o aplicativo novamente']));
  return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

$app->put('/change-password-partner', function (Request $request, Response $response, $args) {
  $input = $request->getParsedBody();
  if (!is_array($input)) {
    $raw = (string) $request->getBody();
    $decodedJson = json_decode($raw, true);
    $input = is_array($decodedJson) ? $decodedJson : [];
  }

  // Corrige o header "Authorization: Bearer <token>"
  $authHeader = $request->getHeaderLine('Authorization');
  $jwtString = preg_replace('/^Bearer\s+/i', '', $authHeader);

  $decoded = JWT::decode($jwtString, new Key($this->get('secret'), $this->get('crypt-type')));


  if (empty($input['senha_atual']) || empty($input['senha_nova'])) {
    $response->getBody()->write(json_encode(['msg' => 'Campo(s) obrigatório(s) não preenchido(s)']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  try {
    $userData = $this->get('db')->prepare("SELECT email, codigo, senha FROM cadastro_parceiro WHERE codigo = :id");
    $userData->bindParam('id', $decoded->id);
    $userData->execute();
  } catch (PDOException $e) {
    $response
      ->getBody()
      ->write(json_encode(['msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: ' . $e->getMessage()]));

    return $response
      ->withHeader('Content-Type', 'application/json')
      ->withStatus(500);
  }

  if ($userData->rowCount() === 0) {
    $response->getBody()->write(json_encode(['msg' => 'Usuário não encontrado. Entre em contato com o suporte']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
  }

  $resUserData = $userData->fetchObject();

  // valida senha atual usando o helper (aceita hash legado e/ou bcrypt)
  $checkAtual = verify_and_upgrade($input['senha_atual'], $resUserData->senha);
  if (!$checkAtual['ok']) {
    $response->getBody()->write(json_encode(['msg' => 'Senha atual inválida']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
  }

  $newPassword = hash_password($input['senha_nova']);

  try {
    $updatePassword = $this->get('db')->prepare('UPDATE cadastro_parceiro set senha = :senha WHERE codigo = :codigo');
    $updatePassword->bindParam('senha', $newPassword);
    $updatePassword->bindParam('codigo', $resUserData->codigo);
    $updatePassword->execute();
  } catch (PDOException $e) {
    $response
      ->getBody()
      ->write(json_encode(['msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: ' . $e->getMessage()]));

    return $response
      ->withHeader('Content-Type', 'application/json')
      ->withStatus(500);
  }

  $emailBody = emailPasswordChanged();

  emailSend('contato@autopop.com.br', 'contato@autopop.com.br', $resUserData->email, 'Alteração de senha: Parceiro - Autopop', $emailBody);

  $response->getBody()->write(json_encode(['msg' => 'Senha alterada com sucesso']));
  return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});