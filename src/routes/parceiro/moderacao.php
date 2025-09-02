<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * GET /precadastros-partner
 * Lista pré-cadastros (tabela: precadastro_parceiro)
 */
$app->get('/precadastros-partner', function (Request $request, Response $response) {
    $db = $this->get('db');

    $params = $request->getQueryParams();
    $q = isset($params['q']) ? trim($params['q']) : '';
    $page = max(1, (int) ($params['page'] ?? 1));
    $limit = min(100, max(1, (int) ($params['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    $where = [];
    $bind = [];
    if ($q !== '') {
        $cnpjDigits = preg_replace('/\D/', '', $q);
        $where[] = '(email LIKE :qEmail OR REPLACE(REPLACE(REPLACE(cnpj,".",""),"/",""),"-","") LIKE :qCnpj)';
        $bind[':qEmail'] = "%{$q}%";
        $bind[':qCnpj'] = "%{$cnpjDigits}%";
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    try {
        $sql = "SELECT id, nome, email, fone, cep, logradouro, numero_endereco, complemento,
                   bairro, cidade, estado, cnpj, razao_social, tipo_atividade, tipo_conta,
                   banco, agencia, conta, administrador
            FROM precadastro_parceiro
            $whereSql
            ORDER BY nome ASC
            LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        foreach ($bind as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sqlCount = "SELECT COUNT(*) AS total FROM precadastro_parceiro $whereSql";
        $stmt2 = $db->prepare($sqlCount);
        foreach ($bind as $k => $v) {
            $stmt2->bindValue($k, $v);
        }
        $stmt2->execute();
        $total = (int) $stmt2->fetchColumn();

        $response->getBody()->write(json_encode([
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'data' => $rows
        ], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(['msg' => 'Erro no banco: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

/**
 * GET /cadastros-partner
 * Lista já cadastrados (tabela: cadastro_parceiro)
 * ⚠️ usa `fone` (sua coluna), não `telefone`
 */
$app->get('/cadastros-partner', function (Request $request, Response $response) {
    $db = $this->get('db');

    $params = $request->getQueryParams();
    $q = isset($params['q']) ? trim($params['q']) : '';
    $page = max(1, (int) ($params['page'] ?? 1));
    $limit = min(100, max(1, (int) ($params['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    $where = [];
    $bind = [];
    if ($q !== '') {
        $cnpjDigits = preg_replace('/\D/', '', $q);
        $where[] = '(email LIKE :qEmail OR REPLACE(REPLACE(REPLACE(cnpj,".",""),"/",""),"-","") LIKE :qCnpj)';
        $bind[':qEmail'] = "%{$q}%";
        $bind[':qCnpj'] = "%{$cnpjDigits}%";
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    try {
        $sql = "SELECT codigo, nome, email, fone, cep, logradouro, numero_endereco, complemento,
                   bairro, cidade, estado, cnpj, razao_social, tipo_atividade, tipo_conta,
                   banco, agencia, conta, administrador, email_confirmado, inativo, ultimo_acesso
            FROM cadastro_parceiro
            $whereSql
            ORDER BY nome ASC
            LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        foreach ($bind as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sqlCount = "SELECT COUNT(*) AS total FROM cadastro_parceiro $whereSql";
        $stmt2 = $db->prepare($sqlCount);
        foreach ($bind as $k => $v) {
            $stmt2->bindValue($k, $v);
        }
        $stmt2->execute();
        $total = (int) $stmt2->fetchColumn();

        $response->getBody()->write(json_encode([
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'data' => $rows
        ], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(['msg' => 'Erro no banco: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

// ========================
/**
 * POST /_debug-auth-partner
 * Diagnóstico de login (DEV): checa se encontra o usuário e se a senha confere.
 * NUNCA deixe isso ativo em produção.
 * Body: { "user": "email@ex.com", "password": "Senha..." }
 */
$app->post('/_debug-auth-partner', function (Request $request, Response $response) {
    if (($_ENV['APP_ENV'] ?? 'dev') !== 'dev') {
        $response->getBody()->write(json_encode(['msg' => 'Indisponível em produção']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }

    $in = $request->getParsedBody();
    $userIn = trim((string) ($in['user'] ?? ''));
    $passIn = (string) ($in['password'] ?? '');

    if ($userIn === '' || $passIn === '') {
        $response->getBody()->write(json_encode(['msg' => 'Informe user e password'], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
    }

    $db = $this->get('db');

    try {
        $q = $db->prepare(
            "SELECT codigo, email, senha, email_confirmado, inativo
         FROM cadastro_parceiro
        WHERE (email = :u OR cnpj = :u)
        LIMIT 1"
        );
        $q->execute([':u' => $userIn]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(['msg' => 'Erro no banco: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    if (!$row) {
        $response->getBody()->write(json_encode([
            'found' => false,
            'why' => 'nao_encontrado_em_cadastro_parceiro'
        ], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }

    $flags = [
        'email_confirmado' => (int) $row['email_confirmado'],
        'inativo' => (int) $row['inativo'],
    ];

    // testa a senha sem expor hash
    $passOk = (crypt($passIn, $row['senha']) === $row['senha']);

    $response->getBody()->write(json_encode([
        'found' => true,
        'id' => (int) $row['codigo'],
        'email' => $row['email'],
        'flags' => $flags,
        'pass_ok' => $passOk
    ], JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * GET /cadastros-partner/by-email?q=...
 * Retorna 1 linha (sem hash) para inspecionar flags rapidamente.
 */
$app->get('/cadastros-partner/by-email', function (Request $request, Response $response) {
    $db = $this->get('db');
    $q = trim((string) ($request->getQueryParams()['q'] ?? ''));

    if ($q === '') {
        $response->getBody()->write(json_encode(['msg' => 'Use ?q=email@ex.com'], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
    }

    try {
        $st = $db->prepare("SELECT codigo, email, email_confirmado, inativo FROM cadastro_parceiro WHERE email = :e LIMIT 1");
        $st->execute([':e' => $q]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(['msg' => 'Erro no banco: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    if (!$row) {
        $response->getBody()->write(json_encode(['found' => false], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }

    $row['email_confirmado'] = (int) $row['email_confirmado'];
    $row['inativo'] = (int) $row['inativo'];
    $response->getBody()->write(json_encode(['found' => true, 'data' => $row], JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json');
});


// ==============================
// DEV ONLY — redefinir senha do parceiro (por id OU email/cnpj)
$app->post('/_reset-pass-partner', function (\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response) {
    if (($_ENV['APP_ENV'] ?? 'dev') !== 'dev') {
        $response->getBody()->write(json_encode(['msg' => 'Indisponível em produção']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }

    $in = $request->getParsedBody();
    if (!is_array($in)) {
        $in = [];
    }
    $id = isset($in['id']) ? (int) $in['id'] : 0;
    $email = isset($in['email']) ? trim((string) $in['email']) : '';
    $user = $email !== '' ? $email : (string) ($in['user'] ?? ''); // aceita "user" também
    $cnpjIn = isset($in['cnpj']) ? preg_replace('/\D/', '', (string) $in['cnpj']) : '';
    $new = (string) ($in['new_password'] ?? '');

    if ($new === '') {
        $response->getBody()->write(json_encode(['msg' => 'Informe "new_password"'], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
    }

    $db = $this->get('db');

    // encontra o parceiro (por id OU por email/cnpj com TRIM/LOWER)
    try {
        if ($id > 0) {
            $sel = $db->prepare("SELECT codigo, email FROM cadastro_parceiro WHERE codigo = :id LIMIT 1");
            $sel->execute([':id' => $id]);
        } else {
            $cnpjDigits = $cnpjIn !== '' ? $cnpjIn : preg_replace('/\D/', '', $user);
            $sel = $db->prepare("
        SELECT codigo, email
          FROM cadastro_parceiro
         WHERE TRIM(LOWER(email)) = TRIM(LOWER(:email))
            OR REPLACE(REPLACE(REPLACE(cnpj,'.',''),'/',''),'-','') = :cnpj
         LIMIT 1
      ");
            $sel->execute([':email' => $user, ':cnpj' => $cnpjDigits]);
        }
        $row = $sel->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $response->getBody()->write(json_encode(['msg' => 'Parceiro não encontrado para reset'], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
    } catch (\PDOException $e) {
        $response->getBody()->write(json_encode(['msg' => 'Erro no banco (select): ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    // mesmo esquema de hash (crypt + COST + salt)
    if (!function_exists('generateRandomSalt')) {
        function generateRandomSalt($len = 22)
        {
            return substr(strtr(base64_encode(random_bytes(16)), '+', '.'), 0, $len);
        }
    }
    $cost = (int) ($_ENV['COST'] ?? 10);
    $salt = generateRandomSalt();
    $hash = crypt($new, '$2a$' . $cost . '$' . $salt . '$');

    try {
        $up = $db->prepare("UPDATE cadastro_parceiro SET senha = :h WHERE codigo = :id");
        $up->execute([':h' => $hash, ':id' => (int) $row['codigo']]);
        if ($up->rowCount() === 0) {
            $response->getBody()->write(json_encode(['msg' => 'Nenhuma linha atualizada'], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    } catch (\PDOException $e) {
        $response->getBody()->write(json_encode(['msg' => 'Erro no banco (update): ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    $response->getBody()->write(json_encode(['msg' => 'Senha atualizada', 'id' => (int) $row['codigo']], JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json');
});
