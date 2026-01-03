<?php
require "config.php";
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $empresa   = $_POST["empresa"];
    $cnpj      = $_POST["cnpj"];
    $telefone  = $_POST["telefone"];
    $whatsapp  = $_POST["whatsapp"];
    $email     = $_POST["email"];
    $instagram = $_POST["instagram"];
    $endereco  = $_POST["endereco"];

    $stmt = $conn->prepare("
        UPDATE settings SET empresa=?, cnpj=?, telefone=?, whatsapp=?, email=?, instagram=?, endereco=? WHERE id=1
    ");
    $stmt->bind_param("sssssss", $empresa, $cnpj, $telefone, $whatsapp, $email, $instagram, $endereco);
    $stmt->execute();
    $msg = "✅ Dados atualizados!";
}

// Carrega
$data = $conn->query("SELECT * FROM settings WHERE id=1")->fetch_assoc();

include "layout_header.php";
?>

<h3>🏢 Dados da Empresa</h3>

<?php if($msg): ?>
<div class="alert alert-success"><?= $msg ?></div>
<?php endif; ?>

<form method="post">
<label>Nome da Empresa</label>
<input name="empresa" class="form-control" value="<?= $data['empresa'] ?>"><br>

<label>CNPJ</label>
<input name="cnpj" class="form-control" value="<?= $data['cnpj'] ?>"><br>

<label>Telefone</label>
<input name="telefone" class="form-control" value="<?= $data['telefone'] ?>"><br>

<label>WhatsApp (com DDD)</label>
<input name="whatsapp" class="form-control" value="<?= $data['whatsapp'] ?>"><br>

<label>Email</label>
<input name="email" class="form-control" value="<?= $data['email'] ?>"><br>

<label>Instagram</label>
<input name="instagram" class="form-control" value="<?= $data['instagram'] ?>"><br>

<label>Endereço</label>
<textarea name="endereco" class="form-control"><?= $data['endereco'] ?></textarea><br>

<button class="btn btn-success">Salvar</button>
</form>

<?php include "layout_footer.php"; ?>