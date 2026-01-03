<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'config.php';

// Se não estiver logado
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

// Buscar usuário logado
$adm = $conn->query("SELECT role FROM usuarios WHERE id=".$_SESSION['user_id'])->fetch_assoc();

// Se não for admin → nega acesso
if(!$adm || $adm['role'] != 'admin'){
    die("<h3 style='color:red;text-align:center;padding:20px;'>❌ Acesso negado — Apenas Administradores</h3>");
}

// ---- SALVAR CONFIGURAÇÕES DO SISTEMA ----
if(isset($_POST['salvar_config'])) {

    $empresa = trim($_POST['empresa']) ?: "Minha Empresa";
    $s1 = trim($_POST['s1']) ?: "Sócio 1";
    $s2 = trim($_POST['s2']) ?: "Sócio 2";
    $reserva = max(0, min(50, floatval($_POST['reserva'])));

    $sql = $conn->prepare("
        UPDATE settings SET empresa=?, socio1=?, socio2=?, reserva=? WHERE id=1
    ");
    $sql->bind_param("sssd", $empresa, $s1, $s2, $reserva);
    $sql->execute();

    $_SESSION['msg'] = "✅ Configurações salvas!";
    header("Location: configuracoes.php");
    exit;
}

// ---- ALTERAR LOGIN ADMIN ----
if(isset($_POST['salvar_login'])) {

    $usuario = trim($_POST['usuario']);
    $senha = trim($_POST['senha']);

    if($usuario != "" && $senha != "") {

        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        $sql = $conn->prepare("UPDATE usuarios SET email=?, senha=? WHERE id=?");
        $sql->bind_param("ssi", $usuario, $senha_hash, $_SESSION['user_id']);
        $sql->execute();

        $_SESSION['msg'] = "✅ Usuário e senha atualizados!";
    } else {
        $_SESSION['msg'] = "⚠️ Preencha usuário e senha!";
    }

    header("Location: configuracoes.php");
    exit;
}

// ---- UPLOAD DE LOGO ----
if(!empty($_FILES['logo_file']['name'])) {

    $dir = "uploads/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $file = time()."_".basename($_FILES["logo_file"]["name"]);
    $dest = $dir.$file;

    if(move_uploaded_file($_FILES["logo_file"]["tmp_name"], $dest)) {
        $conn->query("UPDATE settings SET logo_file='$dest' WHERE id=1");
        $_SESSION['msg'] = "✅ Logo atualizada!";
    } else {
        $_SESSION['msg'] = "❌ Erro ao enviar logo!";
    }

    header("Location: configuracoes.php");
    exit;
}

// Buscar config atual
$conf = $conn->query("SELECT * FROM settings WHERE id=1")->fetch_assoc();

// Buscar usuário atual
$usr = $conn->query("SELECT email FROM usuarios WHERE id=".$_SESSION['user_id'])->fetch_assoc();

include 'layout_header.php';
?>

<h3>⚙️ Configurações do Sistema</h3>

<?php if(isset($_SESSION['msg'])): ?>
<div class="alert alert-info"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
<?php endif; ?>

<!-- CONFIGURAÇÕES GERAIS -->
<form method="post" class="mb-4">
    <input type="hidden" name="salvar_config" value="1">

    <label><b>Nome da Empresa</b></label>
    <input name="empresa" class="form-control mb-2" value="<?= htmlspecialchars($conf['empresa']) ?>">

    <label><b>Nome Sócio 1</b></label>
    <input name="s1" class="form-control mb-2" value="<?= htmlspecialchars($conf['socio1']) ?>">

    <label><b>Nome Sócio 2</b></label>
    <input name="s2" class="form-control mb-2" value="<?= htmlspecialchars($conf['socio2']) ?>">

    <label><b>% Reserva (0–50%)</b></label>
    <input name="reserva" type="number" min="0" max="50" class="form-control mb-3"
    value="<?= htmlspecialchars($conf['reserva']) ?>">

    <button class="btn btn-primary btn-block">Salvar Configurações</button>
</form>

<hr>

<!-- ALTERAR LOGIN -->
<h4>🔑 Alterar Usuário e Senha</h4>

<form method="post" class="mb-4">
    <input type="hidden" name="salvar_login" value="1">

    <label><b>Email de Login</b></label>
    <input name="usuario" type="email" value="<?= htmlspecialchars($usr['email']) ?>" class="form-control mb-2" required>

    <label><b>Nova Senha</b></label>
    <input name="senha" type="password" class="form-control mb-3" required>

    <button class="btn btn-warning btn-block">Atualizar Login</button>
</form>

<hr>

<!-- UPLOAD LOGO -->
<h4>🖼 Logo</h4>
<?php if(!empty($conf['logo_file'])): ?>
<img src="<?= $conf['logo_file'] ?>" height="80" class="mb-2"><br>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <input type="file" name="logo_file" class="form-control mb-2" accept="image/*">
    <button class="btn btn-success btn-block">Enviar Logo</button>
</form>

<?php include 'layout_footer.php'; ?>