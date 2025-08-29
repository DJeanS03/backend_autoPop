<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

$app->get('/buscar-catalogo/{codigo}', function(Request $request, Response $response, $args) {
    $codigo = $args['codigo'];

    try {
        $stmt = $this->get('db')->prepare("
            SELECT 
                catalogo.codigo AS catalogo_codigo,
                catalogo.descricao AS catalogo_descricao,
                veiculo_categoria.`descricao` AS categoria_descricao,
                veiculo_marca.`descricao` AS marca_descricao,
                veiculo_modelo.`descricao` AS modelo_descricao
            FROM catalogo
        LEFT JOIN veiculo_categoria ON cod_categoria_veiculo = veiculo_categoria.`codigo`
        LEFT JOIN veiculo_marca ON cod_marca_veiculo = veiculo_marca.`codigo`
        LEFT JOIN veiculo_modelo ON cod_modelo_veiculo = veiculo_modelo.`codigo`
        WHERE catalogo.codigo = :codigo
        ");
        
        $stmt->bindParam(':codigo', $codigo);
        $stmt->execute();
        $catalogo = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $payload = json_encode($catalogo);
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
        
    } catch(PDOException $e) {
        $payload = json_encode([
            'erro' => 'Erro ao consultar catálogo',
            'mensagem' => $e->getMessage()
        ]);
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});
?>