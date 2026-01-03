<?php
// ============================================
// LOGOUT SEGURO — PLAYTV TECH
// ============================================

// Evita qualquer saída antes dos headers
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_name('financeiro_session');
    session_start();
}

// 🔐 Apaga todos os dados da sessão
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 🔚 Destroi a sessão completamente
session_destroy();

// 🔁 Redireciona para login (usando HTTPS se disponível)
$host = $_SERVER['HTTP_HOST'] ?? 'servicos.playtvtech.xyz';
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
header("Location: {$https}{$host}/login.php");
exit;