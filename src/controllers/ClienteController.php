<?php

namespace src\controllers;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use src\models\Cliente;

class ClienteController
{

    public function cadastro(Request $request, Response $response, $args)
    {
        // Recebendo os dados do cliente via POST
        $data = $request->getParsedBody();

        // Validando os dados (exemplo simples, pode ser mais complexo)
        if (empty($data['nome']) || empty($data['email']) || empty($data['senha'])) {
            return $response->withJson(['msg' => 'Campos obrigatórios não preenchidos'], 400);
        }

        // Inserindo no banco de dados
        $cliente = new Cliente();
        $cliente->nome = $data['nome'];
        $cliente->email = $data['email'];
        $cliente->senha = password_hash($data['senha'], PASSWORD_DEFAULT);  // Armazenando senha de forma segura

        // Salvando no banco
        $cliente->save();  // Você pode usar ORM ou DB Query aqui

        // Respondendo com sucesso
        return $response->withJson(['msg' => 'Cliente cadastrado com sucesso'], 201);
    }
}
