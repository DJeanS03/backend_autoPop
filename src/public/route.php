<?php
// router.php — roteador para o servidor embutido do PHP (php -S)

// 1) servir arquivos estáticos se existirem
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$path = __DIR__ . $uri;
if ($uri !== '/' && file_exists($path) && is_file($path)) {
    return false; // deixa o servidor embutido entregar o arquivo
}

// 2) bootstrap da app
// Ajuste o entrypoint abaixo para o seu "front-controller" real.
// Tente primeiro src/index.php; se não existir, tente src/public/index.php.
$entrypoints = [
    __DIR__ . '/src/index.php',
    __DIR__ . '/src/public/index.php',
];

foreach ($entrypoints as $ep) {
    if (file_exists($ep)) {
        require $ep;
        exit;
    }
}

// 3) fallback amigável se nada foi encontrado
http_response_code(500);
header('Content-Type: text/plain; charset=utf-8');
echo "Entrypoint da aplicação não encontrado.\n";
echo "Crie src/index.php (ou src/public/index.php) e aponte o router para ele.\n";
