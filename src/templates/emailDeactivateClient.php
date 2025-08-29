<?php

function emailDeactivateClient($data){
  return
  "
  <!DOCTYPE html>
  <html>
    <head></head>
    <body>
      <h1>Desativação de conta de cliente - AutoPop</h1>
      <p>Código: {$data['token']}</p>
    </body>
  </html>
  ";
}