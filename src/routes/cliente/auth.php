<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . "/../../helpers/validaCPF.php";
require_once __DIR__ . "/../../helpers/saltGenerate.php";
require_once __DIR__ . "/../../templates/emailCreateClient.php";
require_once __DIR__ . "/../../helpers/emailSend.php";
require_once __DIR__ . "/../../helpers/Password.php";

$app->post('/auth-client', function (Request $request, Response $response, $args) {
  $input = $request->getParsedBody();
  if (!is_array($input)) {
    $raw = (string) $request->getBody();
    $decoded = json_decode($raw, true);
    $input = is_array($decoded) ? $decoded : [];
  }


  //Verifica se os campos de login foram preenchidos
  if (empty($input['user']) || empty($input['password'])) {
    $response->getBody()->write(json_encode(['msg' => 'Campos obrigatórios não preenchidos']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  try {
    $validaUsuario = $this->get('db')->prepare('SELECT id, email, senha, cpf, telefone FROM cadastro_cliente WHERE (email = :user OR telefone = :user) AND email_confirmado <> 0');
    $validaUsuario->bindParam('user', $input['user']);
    $validaUsuario->execute();
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

  //Verifica se o usuário existe
  if ($validaUsuario->rowCount() === 0) {
    $response->getBody()->write(json_encode(['msg' => 'Usuário e/ou Senha incorreto(s) ou usuário ainda não ativado']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
  }

  $resValidaUsuario = $validaUsuario->fetchObject();

  // usa helper (bcrypt preferencial; fallback legado; rehash se necessário)
  $check = verify_and_upgrade($input['password'], $resValidaUsuario->senha);
  if (!$check['ok']) {
    $response->getBody()->write(json_encode(['msg' => 'Usuário e/ou senha incorreto(s) ou usuário ainda não ativado']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
  }

  // se indicou rehash, atualiza a coluna
  if (!empty($check['rehash'])) {
    try {
      $updHash = $this->get('db')->prepare("UPDATE cadastro_cliente SET senha = :hash WHERE id = :id");
      $updHash->execute([
        'hash' => $check['rehash'],
        'id' => $resValidaUsuario->id
      ]);
    } catch (PDOException $e) {
      // não bloqueia o login
    }
  }

  //Criando token
  $payload = [
    'id' => $resValidaUsuario->id,
    'email' => $resValidaUsuario->email,
    'cpf' => $resValidaUsuario->cpf,
    'telefone' => $resValidaUsuario->telefone,
    'tipo_usuario' => 'c',
    'iat' => 1356999524,
    'nbf' => 1357000000
  ];

  $jwt = JWT::encode($payload, $this->get('secret'), $this->get('crypt-type'));

  $response->getBody()->write(json_encode(['token' => $jwt]));
  return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});

$app->post('/create-client-account', function (Request $request, Response $response, $args) use ($app) {
  $input = $request->getParsedBody();
  if (!is_array($input)) {
    $raw = (string) $request->getBody();
    $decoded = json_decode($raw, true);
    $input = is_array($decoded) ? $decoded : [];
  }

  //Valida se os campos obrigatórios foram preenchidos
  if (empty($input['email']) || empty($input['senha']) || empty($input['cep'])) {
    $response->getBody()->write(json_encode(['msg' => 'Campo(s) obrigatório(s) não preenchido(s)']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  //Verifica se o CPF é válido, caso tenha sido preenchido
  if (!empty($input['cpf']) && !validaCPF($input['cpf'])) {
    $response->getBody()->write(json_encode(['msg' => 'CPF Inválido. Favor, tente novamente com CPF válido']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  //Verifica se o email é válido ou não
  if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    $response->getBody()->write(json_encode(['msg' => 'Email inválido. Favor, tente novamente com um email válido']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  $formattedCPF = preg_replace('/[^0-9]/', '', $input['cpf']);

  try {
    $verifyExistence = $this->get('db')->prepare(
      "SELECT id FROM cadastro_cliente WHERE cpf = :cpf OR email = :email"
    );
    $verifyExistence->bindParam('cpf', $formattedCPF);
    $verifyExistence->bindParam('email', $input['email']);
    $verifyExistence->execute();

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

  if ($verifyExistence->rowCount() > 0) {
    $response->getBody()->write(json_encode(['msg' => 'Usuário já registrado. Tente usar a função de recuperar senha ou entre em contato com o suporte']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  //Gerando ID
  $uuid = Uuid::uuid4();

  $password = hash_password($input['senha']);

  //Token de confirmação de conta
  $token = bin2hex(random_bytes(25));

  $formattedCEP = preg_replace('/[^0-9]/', '', $input['cep']);

  try {
    $insertUser = $this->get('db')->prepare("INSERT INTO cadastro_cliente (id, telefone, cpf, email, senha, cep, logradouro, numero_endereco, bairro, cidade, estado, token_confirmacao)
                                            VALUES(:id, :telefone, :cpf, :email, :senha, :cep, :logradouro, :numero, :bairro, :cidade, :estado, :token)");
    $insertUser->bindParam('id', $uuid);
    $insertUser->bindParam('telefone', $input['telefone']);
    $insertUser->bindParam('cpf', $formattedCPF);
    $insertUser->bindParam('email', $input['email']);
    $insertUser->bindParam('senha', $password);
    $insertUser->bindParam('cep', $formattedCEP);
    $insertUser->bindParam('logradouro', $input['logradouro']);
    $insertUser->bindParam('numero', $input['numero_endereco']);
    $insertUser->bindParam('bairro', $input['bairro']);
    $insertUser->bindParam('cidade', $input['cidade']);
    $insertUser->bindParam('estado', $input['estado']);
    $insertUser->bindParam('token', $token);
    $insertUser->execute();
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
    'email' => $input['email'],
    'token' => $token,
    'base_url' => $_ENV['HOST_ADDRESS'] . "/confirm-account-client"
  ];
  $emailBody = emailCreateClient($data_email);

  emailSend('contato@autopop.com.br', 'contato@autopop.com.br', $input['email'], 'Cadastro de Usuário - Autopop', $emailBody);

  $payload = json_encode(['msg' => 'Cadastro feito com sucesso. Verifique o email registrado para confirmar sua conta']);
  $response->getBody()->write($payload);
  return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});

$app->get('/confirm-account-client/{token}', function (Request $request, Response $response, $args) {
  $token_link = $args['token'];

  try {
    $verifyToken = $this->get('db')->prepare("SELECT id FROM cadastro_cliente WHERE token_confirmacao = :token AND email_confirmado = '0'");
    $verifyToken->bindParam('token', $token_link);
    $verifyToken->execute();
  } catch (PDOException $e) {
    $response
      ->getBody()
      ->write(json_encode(['msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: ' . $e->getMessage()]));

    return $response
      ->withHeader('Content-Type', 'application/json')
      ->withStatus(502);
  }

  if ($verifyToken->rowCount() === 0) {
    $response->getBody()->write(json_encode(['msg' => 'Link inválido. Favor, tente novamente com um link válido']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
  }

  $resVerifyToken = $verifyToken->fetchObject();

  try {
    $updateUserInformation = $this->get('db')->prepare("UPDATE cadastro_cliente SET token_confirmacao = NULL, email_confirmado = 1 WHERE id = :id");
    $updateUserInformation->bindParam("id", $resVerifyToken->id);
    $updateUserInformation->execute();
  } catch (PDOException $e) {
    $response
      ->getBody()
      ->write(json_encode(['msg' => 'Erro nno banco de dados. Entre em contato com o suporte com o seguinte erro: ' . $e->getMessage()]));
  }

  $response->getBody()->write(json_encode(['msg' => 'Conta confirmada com sucesso. Pode acessar o aplicativo e fazer sua primeira compra!']));
  return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});