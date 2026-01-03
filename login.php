<?php
// ===========================================
// LOGIN.PHP — SISTEMA TECH ELETRO (BS5)
// CORRIGIDO: Remoção de session_start() duplicado
// ===========================================

// O config.php deve ser o único responsável por iniciar a sessão
require 'config.php'; 

// Evita cache e garante logout efetivo
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Se já estiver logado, redireciona para o painel
if (!empty($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $recaptcha_token = $_POST['g-recaptcha-response'] ?? '';

    // === Verificação reCAPTCHA ===
    if (!$recaptcha_token) {
        $erro = "Por favor, confirme que você não é um robô.";
    } else {
        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret'   => $RECAPTCHA_SECRET,
            'response' => $recaptcha_token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];

        $ch = curl_init($verifyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $res = curl_exec($ch);
        curl_close($ch);

        $resp = json_decode($res, true);
        if (!($resp['success'] ?? false)) {
            $erro = "Falha ao validar o reCAPTCHA. Tente novamente.";
        }
    }

    // === Se captcha OK, processa login ===
    if (empty($erro)) {
        if ($email !== '' && $senha !== '') {
            $stmt = $conn->prepare("SELECT id, nome, senha FROM usuarios WHERE email=? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res && $res->num_rows > 0) {
                $user = $res->fetch_assoc();

                if (password_verify($senha, $user['senha'])) {
                    // ✅ Login OK
                    // A sessão já está ativa, agora apenas a populamos
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['nome']    = $user['nome'];

                    header("Location: index.php");
                    exit;
                } else {
                    $erro = "Senha incorreta.";
                }
            } else {
                $erro = "Usuário não encontrado.";
            }
        } else {
            $erro = "Preencha todos os campos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — <?= htmlspecialchars($EMPRESA_NOME ?? 'PlayTV Tech') ?></title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<style>
body {
    background: linear-gradient(135deg, #0d6efd, #0049b7);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0;
}
.card {
    border-radius: 14px;
    box-shadow: 0 4px 18px rgba(0,0,0,.25);
}
.btn-primary {
    background-color: #0d6efd;
    border: none;
}
.btn-primary:hover {
    background-color: #004bb5;
}
.logo {
    width: 90px;
    margin-bottom: 10px;
}
.spinner-border {
    display: none;
    vertical-align: middle;
    margin-left: 8px;
}
</style>
</head>
<body>
<div class="card p-4" style="width:100%; max-width:380px;">
    <div class="text-center mb-3">
        <img src="<?= $EMPRESA_LOGO ? htmlspecialchars($EMPRESA_LOGO) : 'https://cdn-icons-png.flaticon.com/512/6009/6009888.png' ?>" class="logo" alt="Logo">
        <h4><?= htmlspecialchars($EMPRESA_NOME ?? 'PlayTV Tech') ?></h4>
        <p class="text-muted">Acesse sua conta</p>
    </div>

    <?php if ($erro): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="post" id="loginForm">
        <div class="mb-3">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" class="form-control" required placeholder="Digite seu e-mail">
        </div>
        <div class="mb-3">
            <label class="form-label">Senha</label>
            <input type="password" name="senha" class="form-control" required placeholder="Digite sua senha">
        </div>

        <div class="mb-3 text-center">
            <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($RECAPTCHA_SITEKEY) ?>"></div>
        </div>

        <button class="btn btn-primary w-100" type="submit" id="btnLogin">
            Entrar
            <span class="spinner-border spinner-border-sm" id="spinner" role="status" aria-hidden="true"></span>
        </button>
        <div class="text-center mt-2">
            <a href="senha_reset_pedir.php" class="text-decoration-none">Esqueci minha senha</a>
        </div>
    </form>
</div>

<script>
// Efeito de loading no botão
document.getElementById('loginForm').addEventListener('submit', function() {
    document.getElementById('btnLogin').disabled = true;
    document.getElementById('spinner').style.display = 'inline-block';
});
</script>
</body>
</html>
