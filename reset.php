<?php
session_start();
require 'config.php';

$token = $_GET['token'] ?? '';
if(!$token) die("Token inválido");

$q = $conn->prepare("SELECT id FROM usuarios WHERE reset_token=? AND reset_expires > NOW()");
$q->bind_param("s", $token);
$q->execute();
$r = $q->get_result();

if($r->num_rows == 0) die("Link inválido ou expirado");

$user = $r->fetch_assoc();

if($_POST){
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    $u = $conn->prepare("UPDATE usuarios SET senha=?, reset_token=NULL, reset_expires=NULL WHERE id=?");
    $u->bind_param("si", $senha, $user['id']);
    $u->execute();

    echo "Senha redefinida com sucesso! <a href='login.php'>Entrar</a>";
    exit;
}
?>

<form method="post">
    <h3>Nova senha</h3>
    <input type="password" name="senha" class="form-control" required>
    <button class="btn btn-success mt-3">Salvar</button>
</form>