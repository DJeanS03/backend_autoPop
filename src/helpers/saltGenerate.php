<?php

function generateRandomSalt($length = 22) {
  $salt = '';
  $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
  $maxIndex = strlen($characters) - 1;

  for ($i = 0; $i < $length; $i++) {
      $index = random_int(0, $maxIndex);
      $salt .= $characters[$index];
  }

  return $salt;
}