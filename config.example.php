<?php
/**
 * TECH-ELETRO - ARQUIVO DE CONFIGURAÇÃO (EXEMPLO)
 * 
 * INSTRUÇÕES:
 * 1. Copie este arquivo para 'config.php'
 * 2. Preencha com suas credenciais reais
 * 3. NUNCA commit o arquivo config.php no Git
 * 
 * IMPORTANTE: O arquivo config.php está no .gitignore
 */

// ===================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// ===================================

define('DB_HOST', 'localhost');           // Host do banco de dados
define('DB_NAME', 'tech_eletro');         // Nome do banco de dados
define('DB_USER', 'seu_usuario');         // Usuário do banco
define('DB_PASS', 'sua_senha_segura');    // Senha do banco
define('DB_CHARSET', 'utf8mb4');          // Charset do banco

// ===================================
// CONFIGURAÇÕES DO SISTEMA
// ===================================

define('APP_NAME', 'Tech-Eletro');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'production'); // development, staging, production

// URL base do sistema (sem barra no final)
define('BASE_URL', 'http://localhost/tech-eletro');

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// ===================================
// CONFIGURAÇÕES DE SEGURANÇA
// ===================================

// Chave secreta para criptografia (gere uma chave única)
// Use: bin2hex(random_bytes(32))
define('SECRET_KEY', 'GERE_UMA_CHAVE_ALEATORIA_AQUI_32_BYTES');

// Salt para senhas (gere um salt único)
define('PASSWORD_SALT', 'GERE_UM_SALT_ALEATORIO_AQUI');

// Tempo de expiração da sessão (em segundos)
define('SESSION_TIMEOUT', 3600); // 1 hora

// ===================================
// CONFIGURAÇÕES DE EMAIL
// ===================================

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // tls ou ssl
define('SMTP_USER', 'seu-email@gmail.com');
define('SMTP_PASS', 'sua-senha-de-app');
define('SMTP_FROM_EMAIL', 'seu-email@gmail.com');
define('SMTP_FROM_NAME', 'Tech-Eletro Sistema');

// ===================================
// CONFIGURAÇÕES DE UPLOAD
// ===================================

define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB em bytes
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);
define('UPLOAD_PATH', __DIR__ . '/uploads/');

// ===================================
// CONFIGURAÇÕES DE BACKUP
// ===================================

define('BACKUP_PATH', __DIR__ . '/backups/');
define('BACKUP_AUTO_ENABLED', true);
define('BACKUP_EMAIL', 'seu-email-backup@gmail.com');

// ===================================
// CONFIGURAÇÕES DE LOG
// ===================================

define('LOG_PATH', __DIR__ . '/logs/');
define('LOG_LEVEL', 'ERROR'); // DEBUG, INFO, WARNING, ERROR
define('LOG_ENABLED', true);

// ===================================
// CONFIGURAÇÕES DE DESENVOLVIMENTO
// ===================================

if (APP_ENV === 'development') {
    // Mostrar todos os erros em desenvolvimento
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    // Em produção, não mostrar erros na tela
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_PATH . 'php-error.log');
}

// ===================================
// CONEXÃO COM BANCO DE DADOS
// ===================================

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // Em produção, não expor detalhes do erro
    if (APP_ENV === 'development') {
        die("Erro de conexão: " . $e->getMessage());
    } else {
        die("Erro ao conectar ao banco de dados. Contate o administrador.");
    }
}

// ===================================
// CONFIGURAÇÕES DE SESSÃO
// ===================================

// Configurações seguras de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Mudar para 1 se usar HTTPS
ini_set('session.cookie_samesite', 'Strict');

// Iniciar sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===================================
// FUNÇÕES AUXILIARES
// ===================================

/**
 * Sanitiza string para prevenir XSS
 */
function sanitize($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Valida email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Gera token CSRF
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida token CSRF
 */
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Registra log
 */
function log_message($level, $message) {
    if (!LOG_ENABLED) return;
    
    $levels = ['DEBUG' => 0, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3];
    $current_level = $levels[LOG_LEVEL] ?? 3;
    $log_level = $levels[$level] ?? 0;
    
    if ($log_level >= $current_level) {
        $log_file = LOG_PATH . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] [$level] $message" . PHP_EOL;
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
}

?>
