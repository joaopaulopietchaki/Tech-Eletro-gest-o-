<?php
session_start();
require 'config.php';
require 'email_config.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    $q = $conn->prepare("SELECT id FROM usuarios WHERE email=?");
    $q->bind_param("s", $email);
    $q->execute();
    $r = $q->get_result();

    if ($r->num_rows > 0) {
        $user = $r->fetch_assoc();
        $token = bin2hex(random_bytes(32));
        $expire = date("Y-m-d H:i:s", strtotime("+1 hour"));

        $u = $conn->prepare("UPDATE usuarios SET reset_token=?, reset_expires=? WHERE id=?");
        $u->bind_param("ssi", $token, $expire, $user['id']);
        $u->execute();

        $reset_link = "https://servicos.playtvtech.xyz/reset.php?token=$token";

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = $SMTP_USER;
        $mail->Password = $SMTP_PASS;
        $mail->SMTPSecure = "tls";
        $mail->Port = $SMTP_PORT;
        $mail->setFrom($SMTP_USER, "Sistema Financeiro");
        $mail->addAddress($email);
        $mail->Subject = "Recuperação de Senha";
        $mail->Body = "Clique para redefinir sua senha:\n\n$reset_link";
        $mail->send();

        $msg = "Se o email existir no sistema, você receberá o link.";
    }
}
?>

<form method="post">
    <h3>Recuperar Senha</h3>
    <?= $msg ?? "" ?><br><br>
    <input type="email" name="email" class="form-control" placeholder="Seu email" required>
    <button class="btn btn-primary mt-3">Enviar Link</button>
</form>