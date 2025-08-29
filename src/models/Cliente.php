<?php

namespace src\models;

class Cliente
{
    public $nome;
    public $email;
    public $senha;

    public function save()
    {
        // Conexão com o banco de dados usando PDO
        $pdo = new \PDO('mysql:host=localhost;dbname=autopop', 'usuario', 'senha');

        // Prepare a query de inserção
        $stmt = $pdo->prepare("INSERT INTO cadastro_cliente (nome, email, senha) VALUES (:nome, :email, :senha)");
        $stmt->bindParam(':nome', $this->nome);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':senha', $this->senha);
        $stmt->execute();
    }
}
