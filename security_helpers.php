<?php
/**
 * TECH-ELETRO - FUNÇÕES DE SEGURANÇA
 * 
 * Este arquivo contém funções auxiliares para segurança da aplicação
 * Inclua este arquivo em todas as páginas que precisam de validação e sanitização
 */

// ===================================
// PROTEÇÃO CSRF
// ===================================

/**
 * Gera um token CSRF para formulários
 * 
 * @return string Token CSRF
 */
function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Gera campo hidden com token CSRF para formulários
 * 
 * @return string HTML do campo hidden
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Valida token CSRF do formulário
 * 
 * @param string|null $token Token para validar (se null, pega do POST)
 * @return bool True se válido, False caso contrário
 */
function csrf_validate($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? '';
    }
    
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Valida CSRF e interrompe execução se inválido
 */
function csrf_verify() {
    if (!csrf_validate()) {
        http_response_code(403);
        die('Token CSRF inválido. Por favor, tente novamente.');
    }
}

// ===================================
// SANITIZAÇÃO DE DADOS
// ===================================

/**
 * Sanitiza string para prevenir XSS
 * 
 * @param string $data Dado para sanitizar
 * @return string Dado sanitizado
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Sanitiza string para uso em SQL (complementar ao prepared statements)
 * 
 * @param string $data Dado para sanitizar
 * @return string Dado sanitizado
 */
function sanitize_sql($data) {
    return filter_var($data, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
}

/**
 * Sanitiza email
 * 
 * @param string $email Email para sanitizar
 * @return string Email sanitizado
 */
function sanitize_email($email) {
    return filter_var($email, FILTER_SANITIZE_EMAIL);
}

/**
 * Sanitiza URL
 * 
 * @param string $url URL para sanitizar
 * @return string URL sanitizada
 */
function sanitize_url($url) {
    return filter_var($url, FILTER_SANITIZE_URL);
}

/**
 * Sanitiza número inteiro
 * 
 * @param mixed $number Número para sanitizar
 * @return int Número inteiro sanitizado
 */
function sanitize_int($number) {
    return filter_var($number, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * Sanitiza número float
 * 
 * @param mixed $number Número para sanitizar
 * @return float Número float sanitizado
 */
function sanitize_float($number) {
    return filter_var($number, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}

// ===================================
// VALIDAÇÃO DE DADOS
// ===================================

/**
 * Valida email
 * 
 * @param string $email Email para validar
 * @return bool True se válido
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida URL
 * 
 * @param string $url URL para validar
 * @return bool True se válido
 */
function validate_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Valida CPF
 * 
 * @param string $cpf CPF para validar
 * @return bool True se válido
 */
function validate_cpf($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    for ($t = 9; $t < 11; $t++) {
        $d = 0;
        for ($c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    return true;
}

/**
 * Valida CNPJ
 * 
 * @param string $cnpj CNPJ para validar
 * @return bool True se válido
 */
function validate_cnpj($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    
    if (strlen($cnpj) != 14) {
        return false;
    }
    
    if (preg_match('/(\d)\1{13}/', $cnpj)) {
        return false;
    }
    
    $calc = function($cnpj, $positions) {
        $sum = 0;
        $pos = 0;
        for ($i = 0; $i < strlen($cnpj); $i++) {
            $sum += $cnpj[$i] * $positions[$pos];
            $pos++;
        }
        $result = $sum % 11;
        return $result < 2 ? 0 : 11 - $result;
    };
    
    $pos1 = [5,4,3,2,9,8,7,6,5,4,3,2];
    $pos2 = [6,5,4,3,2,9,8,7,6,5,4,3,2];
    
    $digit1 = $calc(substr($cnpj, 0, 12), $pos1);
    $digit2 = $calc(substr($cnpj, 0, 12) . $digit1, $pos2);
    
    return ($cnpj[12] == $digit1 && $cnpj[13] == $digit2);
}

/**
 * Valida telefone brasileiro
 * 
 * @param string $phone Telefone para validar
 * @return bool True se válido
 */
function validate_phone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return preg_match('/^[1-9]{2}9?[0-9]{8}$/', $phone) === 1;
}

/**
 * Valida data no formato brasileiro (dd/mm/yyyy)
 * 
 * @param string $date Data para validar
 * @return bool True se válida
 */
function validate_date($date) {
    $parts = explode('/', $date);
    if (count($parts) !== 3) {
        return false;
    }
    return checkdate($parts[1], $parts[0], $parts[2]);
}

/**
 * Valida senha forte
 * Mínimo 8 caracteres, 1 maiúscula, 1 minúscula, 1 número, 1 caractere especial
 * 
 * @param string $password Senha para validar
 * @return bool True se válida
 */
function validate_strong_password($password) {
    $uppercase = preg_match('/[A-Z]/', $password);
    $lowercase = preg_match('/[a-z]/', $password);
    $number = preg_match('/[0-9]/', $password);
    $special = preg_match('/[^A-Za-z0-9]/', $password);
    
    return strlen($password) >= 8 && $uppercase && $lowercase && $number && $special;
}

// ===================================
// CONTROLE DE ACESSO
// ===================================

/**
 * Verifica se usuário está autenticado
 * 
 * @return bool True se autenticado
 */
function is_authenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Requer autenticação - redireciona para login se não autenticado
 * 
 * @param string $redirect_to URL para redirecionar após login
 */
function require_auth($redirect_to = '/login.php') {
    if (!is_authenticated()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . $redirect_to);
        exit;
    }
}

/**
 * Verifica se usuário tem uma permissão específica
 * 
 * @param string $permission Permissão necessária
 * @return bool True se tem permissão
 */
function has_permission($permission) {
    if (!is_authenticated()) {
        return false;
    }
    
    // Implementar lógica de verificação de permissões
    // Por exemplo, verificar em um array de permissões do usuário
    return in_array($permission, $_SESSION['user_permissions'] ?? []);
}

/**
 * Requer uma permissão específica
 * 
 * @param string $permission Permissão necessária
 */
function require_permission($permission) {
    if (!has_permission($permission)) {
        http_response_code(403);
        die('Você não tem permissão para acessar esta página.');
    }
}

// ===================================
// UPLOAD DE ARQUIVOS
// ===================================

/**
 * Valida upload de arquivo
 * 
 * @param array $file Array do $_FILES
 * @param array $allowed_types Tipos MIME permitidos
 * @param int $max_size Tamanho máximo em bytes
 * @return array ['success' => bool, 'message' => string, 'file' => array]
 */
function validate_file_upload($file, $allowed_types = [], $max_size = 5242880) {
    // Verificar se arquivo foi enviado
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'message' => 'Nenhum arquivo enviado'];
    }
    
    // Verificar erros de upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Arquivo muito grande (limite do servidor)',
            UPLOAD_ERR_FORM_SIZE => 'Arquivo muito grande (limite do formulário)',
            UPLOAD_ERR_PARTIAL => 'Upload incompleto',
            UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário não encontrado',
            UPLOAD_ERR_CANT_WRITE => 'Erro ao gravar arquivo',
            UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão'
        ];
        return ['success' => false, 'message' => $errors[$file['error']] ?? 'Erro desconhecido'];
    }
    
    // Verificar tamanho
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'Arquivo muito grande. Máximo: ' . ($max_size / 1024 / 1024) . 'MB'];
    }
    
    // Verificar tipo MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!empty($allowed_types) && !in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'message' => 'Tipo de arquivo não permitido'];
    }
    
    return ['success' => true, 'message' => 'Arquivo válido', 'file' => $file, 'mime_type' => $mime_type];
}

/**
 * Gera nome único para arquivo
 * 
 * @param string $original_name Nome original do arquivo
 * @return string Nome único gerado
 */
function generate_unique_filename($original_name) {
    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
    return uniqid('file_', true) . '.' . $extension;
}

// ===================================
// RATE LIMITING (PROTEÇÃO CONTRA BRUTE FORCE)
// ===================================

/**
 * Verifica rate limiting
 * 
 * @param string $key Chave única (ex: IP + ação)
 * @param int $max_attempts Máximo de tentativas
 * @param int $time_window Janela de tempo em segundos
 * @return bool True se dentro do limite
 */
function check_rate_limit($key, $max_attempts = 5, $time_window = 300) {
    $key = 'rate_limit_' . md5($key);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }
    
    $data = $_SESSION[$key];
    
    // Resetar se passou a janela de tempo
    if (time() - $data['first_attempt'] > $time_window) {
        $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
        return true;
    }
    
    // Incrementar contador
    $_SESSION[$key]['count']++;
    
    // Verificar se excedeu o limite
    return $_SESSION[$key]['count'] <= $max_attempts;
}

/**
 * Bloqueia se exceder rate limit
 * 
 * @param string $key Chave única
 * @param int $max_attempts Máximo de tentativas
 * @param int $time_window Janela de tempo em segundos
 */
function require_rate_limit($key, $max_attempts = 5, $time_window = 300) {
    if (!check_rate_limit($key, $max_attempts, $time_window)) {
        http_response_code(429);
        die('Muitas tentativas. Aguarde alguns minutos e tente novamente.');
    }
}

// ===================================
// LOGS DE SEGURANÇA
// ===================================

/**
 * Registra evento de segurança
 * 
 * @param string $event Tipo de evento
 * @param string $details Detalhes do evento
 */
function log_security_event($event, $details = '') {
    $log_file = LOG_PATH . 'security_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user = $_SESSION['user_id'] ?? 'guest';
    
    $log_entry = sprintf(
        "[%s] [%s] [IP: %s] [User: %s] %s\n",
        $timestamp,
        $event,
        $ip,
        $user,
        $details
    );
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

?>
