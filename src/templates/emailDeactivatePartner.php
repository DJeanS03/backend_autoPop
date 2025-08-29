<?php

function emailDeactivatePartner($data){
  return
  "
  <!DOCTYPE html>
  <html>
    <head></head>
    <body>
    <h1>Desativação de acesso de parceiro - AutoPop</h1>
    <p>Código: {$data['token']}</p>
    </body>
  </html>
  ";
}