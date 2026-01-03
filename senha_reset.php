<?php
require 'config.php';
if (session_status()===PHP_SESSION_NONE) session_start();

$token = $_GET['token'] ?? '';
$erro = $ok = null;

if($_SERVER['REQUEST_METHOD']==='POST'){
    $token = $_POST['token'] ?? '';
    $senha = trim($_POST['senha'] ?? '');
    $conf  = trim($_POST['conf'] ?? '');

    if($token==='' || $senha==='' || $conf===''){
        $erro = "Preencha todos os campos.";
    } elseif($senha !== $conf){
        $erro = "As senhas não conferem.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE reset_token=? AND reset_expires >= NOW() LIMIT 1");
        $stmt->bind_param("s",$token);
        $stmt->execute();
        $res = $stmt->get_result();

        if($res && $res->num_rows){
            $u = $res->fetch_assoc();
            $hash = password_hash($senha, PASSWORD_DEFAULT);

            $up = $conn->prepare("UPDATE usuarios SET senha=?, reset_token=NULL, reset_expires=NULL WHERE id=?");
            $up->bind_param("si",$hash,$u['id']);
            $up->execute();

            $ok = "Senha alterada com sucesso! Você já pode entrar.";
        } else {
            $erro = "Token inválido ou expirado.";
        }
    }
}

include 'layout_header.php';
?>
<h3>🔑 Definir nova senha</h3>

<?php if($erro): ?><div class="alert alert-danger"><?= $erro ?></div><?php endif; ?>
<?php if($ok): ?>
  <div class="alert alert-success"><?= $ok ?></div>
  <a href="login.php" class="btn btn-primary">Ir para o login</a>
<?php else: ?>
<form method="post" style="max-width:420px">
  <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
  <label>Nova senha</label>
  <input type="password" class="form-control mb-2" name="senha" required>
  <label>Confirmar nova senha</label>
  <input type="password" class="form-control mb-3" name="conf" required>
  <button class="btn btn-success">Salvar nova senha</button>
  <a href="login.php" class="btn btn-link">Cancelar</a>
</form>
<?php endif; ?>

<?php include 'layout_footer.php'; ?>