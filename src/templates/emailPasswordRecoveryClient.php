<?php

function emailPasswordRecoveryClient($data){
  return
  "
  <!DOCTYPE html>
  <html>
    <head></head>
    <body>
      <h1>Email recuperação de senha de cliente</h1>
      <p>Link de recuperação de senha: {$data['base_url']}/{$data['token']}</p>
    </body>
  </html>
  ";
}