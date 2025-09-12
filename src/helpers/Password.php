<?php
function hash_password(string $plain): string
{
    return password_hash($plain, PASSWORD_BCRYPT);
}

function is_md5_hash(string $h): bool
{
    return strlen($h) === 32 && ctype_xdigit($h);
}

/**
 * Tenta validar a senha com bcrypt; se falhar, tenta legado (MD5/crypt).
 * Em caso de match legado, retorna ['ok'=>true,'rehash'=>'<novo-bcrypt>'] para regravar.
 */
function verify_and_upgrade(string $plain, string $stored): array
{
    if (password_get_info($stored)['algo'] !== 0 && password_verify($plain, $stored)) {
        // bcrypt (ou outro suportado) válido
        if (password_needs_rehash($stored, PASSWORD_BCRYPT)) {
            return ['ok' => true, 'rehash' => hash_password($plain)];
        }
        return ['ok' => true, 'rehash' => null];
    }

    // legado: MD5
    if (is_md5_hash($stored) && md5($plain) === $stored) {
        return ['ok' => true, 'rehash' => hash_password($plain)];
    }

    // legado: crypt (salt antigo), se existir
    if (strpos($stored, '$1$') === 0 || strpos($stored, '$2') === 0 || strpos($stored, '$5$') === 0 || strpos($stored, '$6$') === 0) {
        if (hash_equals(crypt($plain, $stored), $stored)) {
            return ['ok' => true, 'rehash' => hash_password($plain)];
        }
    }

    return ['ok' => false, 'rehash' => null];
}
