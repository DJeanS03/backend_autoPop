<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__."/../../helpers/validaCPF.php";
require_once __DIR__."/../../helpers/saltGenerate.php";
require_once __DIR__."/../../templates/emailCreateMotoboy.php";
require_once __DIR__."/../../helpers/emailSend.php";

$app->post('/precadastro-motoboy', function(Request $request, Response $response, $args){
  $input = $request->getParsedBody();

  //Verifica se os campos obrigatórios foram preenchidos
  if(empty($input['nome']) || empty($input['cpf']) || empty($input['telefone'])
  || empty($input['email']) || empty($input['cep']) || empty($input['veiculo'])
  || empty($input['senha'])){
    $response->getBody()->write(json_encode(['msg' => 'Campo(s) obrigatório(s) não preenchido(s)']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  //Verifica se o CPF digitado é válido
  if(!validaCPF($input['cpf'])){
    $response->getBody()->write(json_encode(['msg' => 'CPF inválido. Favor, tente novamente com um CPF válido']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  //Verifica se o email fornecido é válido
  if(!filter_var($input['email'], FILTER_VALIDATE_EMAIL)){
    $response->getBody()->write(json_encode(['msg' => 'Email inválido. Favor, tente novamente com um email válido']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  $formattedCPF = preg_replace('/[^0-9]/', '', $input['cpf']);

  try{
    $verifyExistence = $this->get('db')->prepare("SELECT id FROM precadastro_motoboy WHERE cpf = :cpf OR email = :email");
    $verifyExistence->bindParam('cpf', $formattedCPF);
    $verifyExistence->bindParam('email', $input['email']);
    $verifyExistence->execute();
  }
  catch(PDOException $e){
    $response
    ->getBody()
    ->write(
      json_encode(
        ['msg' => 'Erro no banco de dados. Entre em contato com o suporte com a seguinte descrição: '. $e->getMessage()
      ])
    );

    return $response
    ->withHeader('Content-Type', 'application/json')
    ->withStatus(500);
  }

  try{
    $verifyExistence2 = $this->get('db')->prepare('SELECT id FROM cadastro_motoboy WHERE cpf = :cpf OR email = :email');
    $verifyExistence2->bindParam('cpf', $formattedCPF);
    $verifyExistence2->bindParam('email', $input['email']);
    $verifyExistence2->execute();
  }
  catch(PDOException $e){
    $response
    ->getBody()
    ->write(
      json_encode(
        ['msg' => 'Erro no banco de dados. Entre em contato com o suporte com a seguinte descrição: '. $e->getMessage()
      ])
    );

    return $response
    ->withHeader('Content-Type', 'application/json')
    ->withStatus(500);
  }

  if($verifyExistence->rowCount() > 0 || $verifyExistence2->rowCount() > 0){
    $response->getBody()->write(json_encode(['msg' => 'Cadastro já encontrado. Tente recuperar sua senha ou entre em contato com o suporte']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  //Gerando id
  $uuid = Uuid::uuid4();

  //Gerando senha criptografada
  $salt = generateRandomSalt();
  $password = crypt($input['senha'], '$2a$'.$_ENV['COST'].'$'.$salt.'$');

  $formattedCEP = preg_replacE('/[^0-9]/', '', $input['cep']);

  try{
    $insertPreRegister = $this->get('db')->prepare('INSERT INTO precadastro_motoboy (id, nome, cpf, telefone, email, cep, estado, cidade, bairro, logradouro, numero_endereco, complemento, veiculo, placa, senha, como_fazer_entregas)
                                                  VALUES(:id, :nome, :cpf, :telefone, :email, :cep, :estado, :cidade, :bairro, :logradouro, :numero_endereco, :complemento, :veiculo, :placa, :senha, :entregas)');
    $insertPreRegister->bindParam('id', $uuid);
    $insertPreRegister->bindParam('nome', $input['nome']);
    $insertPreRegister->bindParam('cpf', $formattedCPF);
    $insertPreRegister->bindParam('telefone', $input['telefone']);
    $insertPreRegister->bindParam('email', $input['email']);
    $insertPreRegister->bindParam('cep', $formattedCEP);
    $insertPreRegister->bindParam('estado', $input['estado']);
    $insertPreRegister->bindParam('cidade', $input['cidade']);
    $insertPreRegister->bindParam('bairro', $input['bairro']);
    $insertPreRegister->bindParam('logradouro', $input['logradouro']);
    $insertPreRegister->bindParam('numero_endereco', $input['numero_endereco']);
    $insertPreRegister->bindParam('complemento', $input['complemento']);
    $insertPreRegister->bindParam('veiculo', $input['veiculo']);
    $insertPreRegister->bindParam('placa', $input['placa']);
    $insertPreRegister->bindParam('senha', $password);
    $insertPreRegister->bindParam('entregas', $input['como_fazer_entregas']);
    $insertPreRegister->execute();
  }
  catch(PDOException $e){
    $response
    ->getBody()
    ->write(
      json_encode(
        ['msg' => 'Erro no banco de dados. Entre em contato com o suporte com a seguinte descrição: '. $e->getMessage()
      ])
    );

    return $response
    ->withHeader('Content-Type', 'application/json')
    ->withStatus(500);
  }

  $data_email = [
    'Name' => $input['nome']
  ];
  $emailBody = emailCreateMotoboy($data_email);

  emailSend('contato@autopop.com.br', 'contato@autopop.com.br', $input['email'], 'Pré Cadastro de Motoboy - Autopop', $emailBody);

  $payload = json_encode(['msg' => 'Requisição executada com sucesso. Foi enviado um comprovante no email designado e em breve, retornaremos nossa resposta nesse mesmo email']);
  $response->getBody()->write($payload);
  return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});

$app->post('/auth-motoboy', function(Request $request, Response $response, $args){
  $input = $request->getParsedBody();

  if(empty($input['user']) || empty($input['password'])){
    $response->getBody()->write(json_encode(['msg' => 'Usuário ou senha não preenchido(s)']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  try{
    $validaUsuario = $this->get('db')->prepare("SELECT id, email, telefone, senha FROM cadastro_motoboy WHERE (cpf = :user OR email = :user) AND email_confirmado <> 0");
    $validaUsuario->bindParam('user', $input['user']);
    $validaUsuario->execute();
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

  //Verifica se trouxe algum dado. Se não, é porque usuário não existe
  if($validaUsuario->rowCount() === 0){
    $response->getBody()->write(json_encode(['msg' => 'Usuário e/ou senha incorreto(s)']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  //Verifica a senha
  $resValidaUsuario = $validaUsuario->fetchObject();

  if(crypt($input['password'], $resValidaUsuario->senha) !== $resValidaUsuario->senha){
    $response->getBody()->write(json_encode(['msg' => 'Usuário e/ou senha incorreto(s)']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  $payload = [
    'id' => $resValidaUsuario->id,
    'email' => $resValidaUsuario->email,
    'telefone' => $resValidaUsuario->telefone,
    'tipo_usuario' => 'm',
    'iat' => 1356999524,
    'nbf' => 1357000000
  ];

  $jwt = JWT::encode($payload, $this->get('secret'), $this->get('crypt-type'));

  $response->getBody()->write(json_encode(['token' => $jwt]));
  return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});