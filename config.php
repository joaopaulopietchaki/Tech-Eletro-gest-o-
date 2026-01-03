<?php
// =========================
// EXIBIÇÃO DE ERROS (somente durante configuração)
// =========================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// =========================
// CONFIGURAÇÃO DE SESSÃO (corrigida)
// =========================

// Só configura e inicia a sessão se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {

    // Nome customizado da sessão
    session_name('financeiro_session');

    // Garante que a pasta de sessão exista
    $session_dir = __DIR__ . '/tmp_sessions';
    if (!is_dir($session_dir)) {
        mkdir($session_dir, 0777, true);
    }

    // Configurações seguras
    ini_set('session.save_path', $session_dir);
    ini_set('session.cookie_path', '/');
    ini_set('session.httponly', 1);
    ini_set('session.use_strict_mode', 1);

    // Inicia sessão
    session_start();
}

// =========================
// DETECÇÃO DE HTTPS (sem loops, compatível com Cloudflare e subdomínios)
// =========================
$host = $_SERVER['HTTP_HOST'] ?? '';
$is_local = preg_match('/localhost|127\.0\.0\.1/', $host);

$is_https = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || $_SERVER['SERVER_PORT'] == 443
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
);

// Força HTTPS apenas no domínio principal, nunca em subdomínios
if (!$is_https && !$is_local && preg_match('/^playtvtech\.xyz$/i', $host)) {
    header('Location: https://' . $host . $_SERVER['REQUEST_URI'], true, 301);
    exit;
}

// =========================
// CONFIGURAÇÃO DO BANCO DE DADOS
// =========================
$host_db = "localhost";
$db      = "joao0106_financeiro";
$user    = "joao0106_financeiro";
$pass    = "padrao203040";

$conn = new mysqli($host_db, $user, $pass, $db);
if ($conn->connect_error) {
    die("<b>Erro MySQL:</b> " . htmlspecialchars($conn->connect_error));
}
$conn->set_charset("utf8mb4");

// =========================
// CARREGAR CONFIGURAÇÕES DO SISTEMA
// =========================
$CFG = [];
$res = $conn->query("SHOW TABLES LIKE 'settings'");
if ($res && $res->num_rows > 0) {
    $q = $conn->query("SELECT * FROM settings WHERE id = 1");
    if ($q && $q->num_rows > 0) {
        $CFG = $q->fetch_assoc();
    }
}

// =========================
// VARIÁVEIS GLOBAIS
// =========================
$EMPRESA_NOME     = $CFG['empresa']   ?? "Tech Eletro";
$EMPRESA_LOGO     = $CFG['logo_file'] ?? "";
$SOCIO1_NOME      = $CFG['socio1']    ?? "Sócio 1";
$SOCIO2_NOME      = $CFG['socio2']    ?? "Sócio 2";
$RESERVA_PERCENT  = isset($CFG['reserva']) ? floatval($CFG['reserva']) : 10;
$EMPRESA_TELEFONE = $CFG['telefone']  ?? "";
$EMPRESA_EMAIL    = $CFG['email']     ?? "";

// Garantir valores padrão válidos
if (!$SOCIO1_NOME) $SOCIO1_NOME = "Sócio 1";
if (!$SOCIO2_NOME) $SOCIO2_NOME = "Sócio 2";
if ($RESERVA_PERCENT < 0)  $RESERVA_PERCENT = 0;
if ($RESERVA_PERCENT > 50) $RESERVA_PERCENT = 50;


// === GOOGLE reCAPTCHA ===
// Substitua abaixo pelas SUAS chaves reais do painel Google
$RECAPTCHA_SITEKEY = '6LcYkQQsAAAAADjpcBWuxlm7-JTi8O3xBldqsWKV';
$RECAPTCHA_SECRET  = '6LcYkQQsAAAAABez_7sWsYVRaWrraHlLvRxAsmqd';
?>
