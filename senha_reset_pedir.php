<?php
require 'config.php';
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email !== '') {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows) {
            $u = $res->fetch_assoc();
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', time() + 3600); // 1 hora

            $conn->query("DELETE FROM reset_senhas WHERE user_id = {$u['id']}");
            $stmt2 = $conn->prepare("INSERT INTO reset_senhas (user_id, token, expira) VALUES (?, ?, ?)");
            $stmt2->bind_param("iss", $u['id'], $token, $expira);
            $stmt2->execute();

            $link = "https://servicos.playtvtech.xyz/senha_reset_nova.php?token=$token";
            $mensagem = "Um link de redefinição foi gerado:<br><a href='$link'>$link</a>";
        } else {
            $mensagem = "E-mail não encontrado.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperar Senha</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card p-4" style="max-width:400px;margin:auto;">
        <h4 class="mb-3 text-center">Recuperar Senha</h4>
        <?php if ($mensagem): ?><div class="alert alert-info"><?= $mensagem ?></div><?php endif; ?>
        <form method="post">
            <input type="email" name="email" class="form-control mb-3" placeholder="Seu e-mail" required>
            <button class="btn btn-primary w-100">Enviar link</button>
        </form>
    </div>
</div>
</body>
</html>
