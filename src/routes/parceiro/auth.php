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
require_once __DIR__ . "/../../helpers/validaCNPJ.php";
require_once __DIR__ . "/../../templates/emailCreatePartner.php";
require_once __DIR__ . "/../../helpers/emailSend.php";

/**
 * -----------------------------
 *  PARCEIRO - CRIAÇÃO (pré-cadastro)
 *  POST /precadastro-partner
 * -----------------------------
 */
$app->post('/precadastro-partner', function (Request $request, Response $response) {
  $input = $request->getParsedBody();
  if (!is_array($input)) {
    $input = [];
  }

  // Campos obrigatórios (todos os usados no INSERT)
  $required = [
    'nome',
    'email',
    'fone',
    'cep',
    'logradouro',
    'numero_endereco',
    'complemento',
    'bairro',
    'cidade',
    'estado',
    'cnpj',
    'razao_social',
    'tipo_atividade',
    'tipo_conta',
    'banco',
    'agencia',
    'conta',
    'senha',
    'administrador'
  ];

  $missing = [];
  foreach ($required as $f) {
    if (!isset($input[$f]) || $input[$f] === '' || (is_array($input[$f]) && empty($input[$f]))) {
      $missing[] = $f;
    }
  }

  // validações simples
  $invalid = [];
  if (isset($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    $invalid['email'] = 'email_invalido';
  }
  if (isset($input['cnpj']) && !validarCnpj($input['cnpj'])) {
    $invalid['cnpj'] = 'cnpj_invalido';
  }
  if (isset($input['numero_endereco']) && filter_var($input['numero_endereco'], FILTER_VALIDATE_INT) === false) {
    $invalid['numero_endereco'] = 'inteiro_invalido';
  }
  if (isset($input['tipo_atividade']) && filter_var($input['tipo_atividade'], FILTER_VALIDATE_INT) === false) {
    $invalid['tipo_atividade'] = 'inteiro_invalido';
  }
  if (isset($input['tipo_conta']) && filter_var($input['tipo_conta'], FILTER_VALIDATE_INT) === false) {
    $invalid['tipo_conta'] = 'inteiro_invalido';
  }
  if (isset($input['banco']) && filter_var($input['banco'], FILTER_VALIDATE_INT) === false) {
    $invalid['banco'] = 'inteiro_invalido';
  }
  if (isset($input['administrador']) && filter_var($input['administrador'], FILTER_VALIDATE_INT) === false) {
    $invalid['administrador'] = 'inteiro_invalido';
  }

  if (!empty($missing) || !empty($invalid)) {
    $response->getBody()->write(json_encode([
      'msg' => 'Erros de validação',
      'missing' => $missing,
      'invalid' => $invalid
    ], JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
  }

  // normalizações
  $formattedCNPJ = preg_replace('/\D/', '', $input['cnpj']);
  $formattedCEP = preg_replace('/\D/', '', $input['cep']);
  $formattedFone = preg_replace('/\D/', '', $input['fone']);

  // duplicidade (pré-cadastro e cadastro)
  try {
    $verify1 = $this->get('db')->prepare('SELECT id FROM precadastro_parceiro WHERE email = :email OR cnpj = :cnpj');
    $verify1->execute(['email' => $input['email'], 'cnpj' => $formattedCNPJ]);

    $verify2 = $this->get('db')->prepare('SELECT codigo FROM cadastro_parceiro WHERE email = :email OR cnpj = :cnpj');
    $verify2->execute(['email' => $input['email'], 'cnpj' => $formattedCNPJ]);

    if ($verify1->rowCount() > 0 || $verify2->rowCount() > 0) {
      $origem = $verify2->rowCount() > 0 ? 'cadastro' : 'precadastro';
      $response->getBody()->write(json_encode([
        'msg' => 'Parceiro já existe',
        'detail' => [
          'origem' => $origem,
          'hint' => $origem === 'cadastro'
            ? 'Faça login ou recupere a senha.'
            : 'Aguarde aprovação ou use a rota de aprovação em DEV.'
        ]
      ], JSON_UNESCAPED_UNICODE));
      return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
    }
  } catch (PDOException $e) {
    $response->getBody()->write(json_encode([
      'msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
  }

  if ($verify1->rowCount() > 0 || $verify2->rowCount() > 0) {
    $response->getBody()->write(json_encode([
      'msg' => 'Parceiro já cadastrado. Aguarde a resposta de nossa central ou entre em contato com o suporte'
    ], JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(409); // conflito
  }

  // id + senha
  $uuid = Uuid::uuid4();
  $salt = generateRandomSalt();
  $cost = (int) ($_ENV['COST'] ?? 10);
  $password = crypt($input['senha'], '$2a$' . $cost . '$' . $salt . '$');

  // insert em precadastro_parceiro (mantendo seu fluxo)
  try {
    $stmt = $this->get('db')->prepare(
      "INSERT INTO precadastro_parceiro
      (id, nome, email, fone, cep, logradouro, numero_endereco, complemento, bairro, cidade, estado, cnpj, razao_social, tipo_atividade, tipo_conta, banco, agencia, conta, senha, administrador)
      VALUES
      (:id, :nome, :email, :fone, :cep, :logradouro, :numero_endereco, :complemento, :bairro, :cidade, :estado, :cnpj, :razao_social, :tipo_atividade, :tipo_conta, :banco, :agencia, :conta, :senha, :administrador)"
    );

    $stmt->execute([
      'id' => $uuid,
      'nome' => $input['nome'],
      'email' => $input['email'],
      'fone' => $formattedFone,
      'cep' => $formattedCEP,
      'logradouro' => $input['logradouro'],
      'numero_endereco' => $input['numero_endereco'],
      'complemento' => $input['complemento'],
      'bairro' => $input['bairro'],
      'cidade' => $input['cidade'],
      'estado' => $input['estado'],
      'cnpj' => $formattedCNPJ,
      'razao_social' => $input['razao_social'],
      'tipo_atividade' => (int) $input['tipo_atividade'],
      'tipo_conta' => (int) $input['tipo_conta'],
      'banco' => (int) $input['banco'],
      'agencia' => (string) $input['agencia'],
      'conta' => (string) $input['conta'],
      'senha' => $password,
      'administrador' => (int) $input['administrador']
    ]);
  } catch (PDOException $e) {
    $response->getBody()->write(json_encode([
      'msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
  }

  // e-mail de confirmação / recibo (mantém seu template)
  $data_email = ['nome' => $input['nome']];
  $emailBody = emailCreatePartner($data_email);
  emailSend('contato@autopop.com.br', 'contato@autopop.com.br', $input['email'], 'Cadastro de Parceiro - Autopop', $emailBody);

  $response->getBody()->write(json_encode([
    'msg' => 'Requisição executada com sucesso. Foi enviado um comprovante no email designado e em breve, retornaremos a nossa resposta no email que foi descrito'
  ], JSON_UNESCAPED_UNICODE));
  return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});

/**
 * -----------------------------
 *  PARCEIRO - AUTENTICAÇÃO
 *  POST /auth-partner
 * -----------------------------
 */
$app->post('/auth-partner', function (Request $request, Response $response) {
  $input = $request->getParsedBody();
  if (!is_array($input)) {
    $input = [];
  }

  if (empty($input['user']) || empty($input['password'])) {
    $missing = [];
    if (empty($input['user'])) {
      $missing[] = 'user';
    }
    if (empty($input['password'])) {
      $missing[] = 'password';
    }
    $response->getBody()->write(json_encode([
      'msg' => 'Erros de validação',
      'missing' => $missing
    ], JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
  }

  // aceita e-mail OU cnpj
  try {
    $q = $this->get('db')->prepare(
      "SELECT codigo, email, administrador, senha
         FROM cadastro_parceiro
        WHERE (cnpj = :user OR email = :user)
          AND email_confirmado = 1
          AND inativo = 0
        LIMIT 1"
    );
    $q->execute(['user' => $input['user']]);
  } catch (PDOException $e) {
    $response->getBody()->write(json_encode([
      'msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
  }

  if ($q->rowCount() === 0) {
    $response->getBody()->write(json_encode(['msg' => 'Usuário e/ou senha incorreto(s)'], JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  $user = $q->fetchObject();

  // verifica senha (crypt com salt contido no próprio hash)
  if (crypt($input['password'], $user->senha) !== $user->senha) {
    $response->getBody()->write(json_encode(['msg' => 'Usuário e/ou senha incorreto(s)'], JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
  }

  // atualiza último acesso
  try {
    $upd = $this->get('db')->prepare("UPDATE cadastro_parceiro SET ultimo_acesso = NOW() WHERE codigo = :codigo");
    $upd->execute(['codigo' => $user->codigo]);
  } catch (PDOException $e) {
    $response->getBody()->write(json_encode([
      'msg' => 'Erro no banco de dados. Entre em contato com o suporte com o seguinte erro: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
  }

  // JWT com tempos corretos
  $now = time();
  $payload = [
    "id" => (int) $user->codigo,
    "email" => $user->email,
    "administrador" => (int) $user->administrador,
    "tipo_usuario" => 'p',
    'iat' => $now,
    'nbf' => $now,
    'exp' => $now + (60 * 60 * 8) // 8h
  ];

  $jwt = JWT::encode($payload, $this->get('secret'), $this->get('crypt-type'));

  $response->getBody()->write(json_encode(['token' => $jwt], JSON_UNESCAPED_UNICODE));
  return $response->withHeader('Content-Type', 'application/json'); // 200 OK
});

// APROVAR PRÉ-CADASTRO -> CADASTRO (DEV/ADMIN)
// POST /aprovar-partner
$app->post('/aprovar-partner', function (\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response) {
  // por segurança simples, só habilite em dev:
  if (($_ENV['APP_ENV'] ?? 'dev') !== 'dev') {
    $response->getBody()->write(json_encode(['msg' => 'Endpoint indisponível em produção']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
  }

  $input = $request->getParsedBody();
  if (!is_array($input)) {
    $input = [];
  }
  if (empty($input['email']) && empty($input['id'])) {
    $response->getBody()->write(json_encode(['msg' => 'Informe "email" ou "id" do pré-cadastro'], JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
  }

  $db = $this->get('db');

  try {
    // carrega do pré-cadastro por email (com TRIM) ou por id
    if (!empty($input['email'])) {
      $sel = $db->prepare("SELECT * FROM precadastro_parceiro WHERE TRIM(LOWER(email)) = TRIM(LOWER(:email)) LIMIT 1");
      $sel->execute(['email' => $input['email']]);
    } else {
      $sel = $db->prepare("SELECT * FROM precadastro_parceiro WHERE id = :id LIMIT 1");
      $sel->execute(['id' => $input['id']]);
    }
    $pre = $sel->fetch(\PDO::FETCH_ASSOC);

    if (!$pre) {
      $response->getBody()->write(json_encode(['msg' => 'Pré-cadastro não encontrado'], JSON_UNESCAPED_UNICODE));
      return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    // evita duplicidade no cadastro
    $check = $db->prepare("SELECT codigo FROM cadastro_parceiro WHERE email = :email OR cnpj = :cnpj LIMIT 1");
    $check->execute([
      'email' => $pre['email'],
      'cnpj' => preg_replace('/\D/', '', $pre['cnpj'])
    ]);
    if ($check->rowCount() > 0) {
      $response->getBody()->write(json_encode(['msg' => 'Já existe um cadastro com este e-mail/CNPJ'], JSON_UNESCAPED_UNICODE));
      return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
    }

    // insere no cadastro_parceiro (email_confirmado=1, inativo=0)
    $ins = $db->prepare("
  INSERT INTO cadastro_parceiro
    (nome, email, fone, cep, logradouro, numero_endereco, complemento, bairro, cidade, estado,
     cnpj, razao_social, tipo_atividade, tipo_conta, banco, agencia, conta,
     senha, administrador, email_confirmado, inativo, ultimo_acesso)
  VALUES
    (:nome, :email, :fone, :cep, :logradouro, :numero_endereco, :complemento, :bairro, :cidade, :estado,
     :cnpj, :razao_social, :tipo_atividade, :tipo_conta, :banco, :agencia, :conta,
     :senha, :administrador, 1, 0, NOW())
");
    $ins->execute([
      'nome' => $pre['nome'],
      'email' => $pre['email'],
      'fone' => preg_replace('/\D/', '', $pre['fone']),  // ⬅️ aqui também
      'cep' => preg_replace('/\D/', '', $pre['cep']),
      'logradouro' => $pre['logradouro'],
      'numero_endereco' => $pre['numero_endereco'],
      'complemento' => $pre['complemento'],
      'bairro' => $pre['bairro'],
      'cidade' => $pre['cidade'],
      'estado' => $pre['estado'],
      'cnpj' => preg_replace('/\D/', '', $pre['cnpj']),
      'razao_social' => $pre['razao_social'],
      'tipo_atividade' => (int) $pre['tipo_atividade'],
      'tipo_conta' => (int) $pre['tipo_conta'],
      'banco' => (int) $pre['banco'],
      'agencia' => (string) $pre['agencia'],
      'conta' => (string) $pre['conta'],
      'senha' => $pre['senha'],
      'administrador' => (int) $pre['administrador'],
    ]);


    $novoId = (int) $db->lastInsertId();

    // opcional: remover do pré-cadastro
    // $del = $db->prepare("DELETE FROM precadastro_parceiro WHERE id = :id");
    // $del->execute(['id'=>$pre['id']]);

    $response->getBody()->write(json_encode([
      'msg' => 'Parceiro aprovado e migrado para cadastro.',
      'id' => $novoId
    ], JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

  } catch (\PDOException $e) {
    $response->getBody()->write(json_encode(['msg' => 'Erro no banco: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
  }
});
