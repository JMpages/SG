<?php
/**
 * Lógica de Autologin (Recordarme)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no hay sesión pero hay cookie de 'recuerdame'
if (!isset($_SESSION['usuario']) && isset($_COOKIE['recuerdame']) && isset($pdo)) {
    $cookie = $_COOKIE['recuerdame'];
    $restored = false;

    // Intentar formato firmado (base64 of id|exp|hmac)
    $maybe = base64_decode($cookie, true);
    if ($maybe !== false && substr_count($maybe, '|') >= 2 && isset($app_secret) && !empty($app_secret)) {
        list($uid, $exp, $hmac) = explode('|', $maybe, 3);
        if (ctype_digit((string)$uid) && ctype_digit((string)$exp) && time() <= intval($exp)) {
            $payload = $uid . '|' . $exp;
            $calc = hash_hmac('sha256', $payload, $app_secret);
            if (hash_equals($calc, $hmac)) {
                // Restaurar sesión por id
                try {
                    $stmt = $pdo->prepare("SELECT id, username FROM usuarios WHERE id = ?");
                    $stmt->execute([$uid]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($user) {
                        $_SESSION['usuario_id'] = $user['id'];
                        $_SESSION['usuario'] = $user['username'];
                        $restored = true;
                    }
                } catch (Exception $e) {
                    // ignorar
                }
            }
        }
    }

    if (!$restored) {
        // Fallback: intentar buscar token en DB (compatibilidad con implementaciones previas)
        try {
            $stmt = $pdo->prepare("SELECT id, username FROM usuarios WHERE remember_token = ?");
            $stmt->execute([$cookie]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario'] = $user['username'];
                $restored = true;
            }
        } catch (Exception $e) {
            // probable: columna no existe, ignorar
        }
    }

    if (!$restored) {
        // Ningún método funcionó: limpiar cookie
        setcookie('recuerdame', '', time() - 3600, '/');
    }
}

// Limpiar cookie legacy si existe
if (isset($_COOKIE['usuario_sesion'])) {
    setcookie('usuario_sesion', '', time() - 3600, '/');
}
?>