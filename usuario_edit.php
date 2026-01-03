<?php
// Esse sistema trabalha com apenas 1 usuário administrador
// Página desativada

require 'config.php';
if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// Segurança: só exibe mensagem
include 'layout_header.php';
?>

<div class="alert alert-warning mt-3">
    🚫 Este sistema usa apenas 1 usuário administrador.<br>
    Para alterar email e senha, vá em:<br><br>
    <b>⚙️ Configurações → Alterar Login</b>
</div>

<a href="configuracoes.php" class="btn btn-primary mt-3">Ir para Configurações</a>

<?php include 'layout_footer.php'; ?>