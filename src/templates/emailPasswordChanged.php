<?php

function emailPasswordChanged($data =  null){
  return
  "
  <!DOCTYPE html>
  <html>
    <head></head>
    <body>
    <h1>Senha alterada</h1>
    <p>Email de aviso de que sua senha foi alterada</p>
    </body>
  </html>
  ";
}