<?php

function emailDeactivateMotoboy($data){
  return
  "
  <!DOCTYPE html>
  <html>
    <head></head>
    <body>
    <h1>Desativação de acesso de motoboy - AutoPop</h1>
    <p>Código: {$data['token']}</p>
    </body>
  </html>
  ";
}