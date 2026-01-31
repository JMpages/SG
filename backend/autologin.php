<?php
// Verificar si hay sesión iniciada, si no, iniciarla
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no hay usuario en sesión pero hay cookie, intentar loguear
if (!isset($_SESSION['usuario']) && isset($_COOKIE['usuario_sesion']) && isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT id, username FROM usuarios WHERE id = ?");
        $stmt->execute([$_COOKIE['usuario_sesion']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['usuario'] = $user['username'];
            $_SESSION['usuario_id'] = $user['id'];
        }
    } catch (Exception $e) {
        // Fallo silencioso, el usuario tendrá que loguearse manualmente
    }
}
?>