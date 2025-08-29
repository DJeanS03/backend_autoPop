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
require_once __DIR__."/../../helpers/validaCNPJ.php";
require_once __DIR__."/../../templates/emailCreatePartner.php";
require_once __DIR__."/../../helpers/emailSend.php";

$app->post('/precadastro-partner', function(Request $request, Response $response, $args){
  $input = $request->getParsedBody();

  //Verifica se os campos obrigatórios foram preenchidos
  if(empty($input['nome']) || empty($input['email']) || empty($input['fone']) 
    || empty($input['cnpj']) || empty($input['senha'])){
      $response->getBody()->write(json_encode(['msg' => 'Campo(s) obrigatório(s) não preenchido(s)']));
      return $response->withHeader('Content-type', 'application/json')->withStatus(401);
  }

  //Verifica se o CNPJ é válido
  if(!validarCnpj($input['cnpj'])){
    $response->getBody()->write(json_encode(['msg' => 'CNPJ inválido. Favor, tente novamente com um CNPJ válido']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  //Verifica se o email é válido
  if(!filter_var($input['email'], FILTER_VALIDATE_EMAIL)){
    $response->getBody()->write(json_encode(['msg' => 'Email inválido. Favor, tente novamente com um email válido']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  $formattedCNPJ = preg_replace('/[^0-9]/', '', $input['cnpj']);

  //Verifica se já foi cadastrado
  try{
    $verifyExistence = $this->get('db')->prepare('SELECT id FROM precadastro_parceiro WHERE email = :email OR cnpj = :cnpj');
    $verifyExistence->bindParam('email', $input['email']);
    $verifyExistence->bindParam('cnpj', $formattedCNPJ);
    $verifyExistence->execute();
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
    $verifyExistence2 = $this->get('db')->prepare('SELECT codigo FROM cadastro_parceiro WHERE email = :email OR cnpj = :cnpj');
    $verifyExistence2->bindParam('email', $input['email']);
    $verifyExistence2->bindParam('cnpj', $formattedCNPJ);
    $verifyExistence2->execute();
  }
  catch(PDOException){
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

  if($verifyExistence->rowCount() > 0 || $verifyExistence2->rowCount() > 0){
    $response->getBody()->write(json_encode(['msg' => 'Parceiro já cadastrado. Aguarde a resposta de nossa central ou entre em contato com o suporte']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }
  $uuid = Uuid::uuid4();

  $salt = generateRandomSalt();
  $password = crypt($input['senha'], '$2a$'.$_ENV['COST'].'$'.$salt.'$');

  $formattedCEP = preg_replace('/[^0-9]/', '', $input['cep']);
  $formattedFone = preg_replace('/[^0-9]/', '', $input['fone']);

  try{
    $insertPartner = $this->get('db')->prepare("INSERT INTO precadastro_parceiro(id, nome, email, fone, cep, logradouro, numero_endereco, complemento, bairro, cidade, estado, cnpj, razao_social, tipo_atividade, tipo_conta, banco, agencia, conta, senha, administrador)
                                              VALUES(:id, :nome, :email, :fone, :cep, :logradouro, :numero_endereco, :complemento, :bairro, :cidade, :estado, :cnpj, :razaosocial, :tipoatividade, :tipoconta, :banco, :agencia, :conta, :senha, :adm)");
    $insertPartner->bindParam('id', $uuid);
    $insertPartner->bindParam('nome', $input['nome']);
    $insertPartner->bindParam('email', $input['email']);
    $insertPartner->bindParam('fone', $formattedFone);
    $insertPartner->bindParam('cep', $formattedCEP);
    $insertPartner->bindParam('logradouro', $input['logradouro']);
    $insertPartner->bindParam('numero_endereco', $input['numero_endereco']);
    $insertPartner->bindParam('complemento', $input['complemento']);
    $insertPartner->bindParam('bairro', $input['bairro']);
    $insertPartner->bindParam('cidade', $input['cidade']);
    $insertPartner->bindParam('estado', $input['estado']);
    $insertPartner->bindParam('cnpj', $formattedCNPJ);
    $insertPartner->bindParam('razaosocial', $input['razao_social']);
    $insertPartner->bindParam('tipoatividade', $input['tipo_atividade']);
    $insertPartner->bindParam('tipoconta', $input['tipo_conta']);
    $insertPartner->bindParam('banco', $input['banco']);
    $insertPartner->bindParam('agencia', $input['agencia']);
    $insertPartner->bindParam('conta', $input['conta']);
    $insertPartner->bindParam('senha', $password);
    $insertPartner->bindParam('adm', $input['administrador']);
    $insertPartner->execute();
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
    'nome' => $input['nome']
  ];
  $emailBody = emailCreatePartner($data_email);

  emailSend('contato@autopop.com.br', 'contato@autopop.com.br', $input['email'], 'Cadastro de Parceiro - Autopop', $emailBody);

  $response->getBody()->write(json_encode(['msg' => 'Requisição executada com sucesso. Foi enviado um comprovante no email designado e em breve, retornaremos a nossa resposta no email que foi descrito']));
  return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});

$app->post('/auth-partner', function(Request $request, Response $response, $args){
  $input = $request->getParsedBody();

  if(empty($input['user']) || empty($input['password'])){
    $response->getBody()->write(json_encode(['msg' => 'Usuario ou senha não preenchido(s)']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  try{
    $validaUsuario = $this->get('db')->prepare("SELECT codigo, email, administrador, senha FROM cadastro_parceiro WHERE (cnpj = :user OR email = :user) AND email_confirmado = 1 AND inativo = 0");
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

  if($validaUsuario->rowCount() === 0){
    $response->getBody()->write(json_encode(['msg' => 'Usuário e/ou senha incorreto(s)']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  //Verifica senha
  $resValidaUsuario = $validaUsuario->fetchObject();
  if(crypt($input['password'], $resValidaUsuario->senha) !== $resValidaUsuario->senha){
    $response->getBody()->write(json_encode(['msg' => 'Usuário e/ou senha incorreto(s)']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  try{
    $updateLastAccess = $this->get('db')->prepare("UPDATE cadastro_parceiro SET ultimo_acesso = NOW() WHERE codigo = :codigo");
    $updateLastAccess->bindParam('codigo', $resValidaUsuario->codigo);
    $updateLastAccess->execute();
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

  $payload = [
    "id" => $resValidaUsuario->codigo,
    "email" => $resValidaUsuario->email,
    "administrador" => $resValidaUsuario->administrador,
    "tipo_usuario" => 'p',
    'iat' => 1356999524,
    'nbf' => 1357000000
  ];

  $jwt = JWT::encode($payload, $this->get('secret'), $this->get('crypt-type'));

  $response->getBody()->write(json_encode(['token' => $jwt]));
  return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});