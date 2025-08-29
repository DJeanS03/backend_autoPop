<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

$app->get('/listar-produtos', function(Request $request, Response $response) {
    try {
        $stmt = $this->get('db')->prepare("
        SELECT 
            produtos.codigo AS produto_codigo,
            codigo_interno,
            cod_parceiro,
            produtos.descricao AS produto_descricao,
            veiculo,
            valor_final,
            veiculo_categoria.descricao AS categoria_descricao,
            veiculo_marca.descricao AS marca_descricao,
            veiculo_modelo.descricao AS modelo_descricao,
            ano_veiculo
        FROM produtos
        LEFT JOIN veiculo_categoria ON cod_categoria_veiculo = veiculo_categoria.`codigo`
        LEFT JOIN veiculo_marca ON cod_marca_veiculo = veiculo_marca.`codigo`
        LEFT JOIN veiculo_modelo ON cod_modelo_veiculo = veiculo_modelo.`codigo`
        ");
        
        $stmt->execute();
        $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $payload = json_encode($produtos);
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
        
    } catch(PDOException $e) {
        $payload = json_encode([
            'erro' => 'Erro ao consultar produtos',
            'mensagem' => $e->getMessage()
        ]);
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});
?>