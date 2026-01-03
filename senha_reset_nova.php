<?php
require 'config.php';

$token = $_GET['token'] ?? '';
$novaSenha = '';
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token']);
    $novaSenha = trim($_POST['senha']);

    $stmt = $conn->prepare("SELECT user_id, expira FROM reset_senhas WHERE token=? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows) {
        $r = $res->fetch_assoc();
        if (strtotime($r['expira']) > time()) {
            $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
            $up = $conn->prepare("UPDATE usuarios SET senha=? WHERE id=?");
            $up->bind_param("si", $hash, $r['user_id']);
            $up->execute();

            $conn->query("DELETE FROM reset_senhas WHERE user_id=" . (int)$r['user_id']);
            $mensagem = "Senha redefinida com sucesso! <a href='login.php'>Fazer login</a>";
        } else {
            $mensagem = "Token expirado. Peça um novo link.";
        }
    } else {
        $mensagem = "Token inválido.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nova Senha</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card p-4" style="max-width:400px;margin:auto;">
        <h4 class="mb-3 text-center">Nova Senha</h4>
        <?php if ($mensagem): ?><div class="alert alert-info"><?= $mensagem ?></div><?php endif; ?>
        <?php if (!$mensagem): ?>
        <form method="post">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <input type="password" name="senha" class="form-control mb-3" placeholder="Digite a nova senha" required>
            <button class="btn btn-success w-100">Salvar nova senha</button>
        </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
