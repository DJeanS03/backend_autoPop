<?php

function emailPasswordRecoveryPartner($data){
  return
  "
  <!DOCTYPE html>
  <html>
    <head></head>
    <body>
      <h1>Email de recuperação de senha de Parceiro</h1>
      <p>Link de recuperação de senha: {$data['base_url']}/{$data['token']}</p>
    </body>
  </html>
  ";
}