<?php

function emailCreateClient($data){

  return 
  "<!DOCTYPE html>
  <html>
    <head></head>
    <body>
      <h1>Teste envio email</h1>
      <p>Link de ativação de conta: {$data['base_url']}/{$data['token']}</p>
    </body>
  </html>
  ";
}