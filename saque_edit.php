<?php
require "config.php";
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

if (!isset($_GET['id'])) {
    die("ID inválido");
}

$id = intval($_GET['id']);

// BUSCAR SAQUE
$q = $conn->prepare("SELECT * FROM saques WHERE id = ?");
$q->bind_param("i", $id);
$q->execute();
$saque = $q->get_result()->fetch_assoc();

if (!$saque) {
    die("Saque não encontrado");
}

// SALVAR ALTERAÇÃO
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $socio = intval($_POST['socio']);
    $valor = floatval($_POST['valor']);
    $data = $_POST['data'];
    $obs = $_POST['obs'];

    $u = $conn->prepare("UPDATE saques SET socio_num=?, valor=?, data=?, obs=? WHERE id=?");
    $u->bind_param("idssi", $socio, $valor, $data, $obs, $id);
    $u->execute();

    $_SESSION['msg'] = "✅ Saque atualizado com sucesso!";
    header("Location: socio.php");
    exit;
}

include "layout_header.php";
?>

<h3>✏️ Editar Saque</h3>

<form method="post" class="card card-body shadow-sm" style="max-width:500px;">

<label>Sócio</label>
<select name="socio" class="form-control mb-2">
    <option value="1" <?= $saque['socio_num']==1 ? 'selected' : '' ?>><?= $SOCIO1_NOME ?></option>
    <option value="2" <?= $saque['socio_num']==2 ? 'selected' : '' ?>><?= $SOCIO2_NOME ?></option>
</select>

<label>Valor</label>
<input type="number" step="0.01" name="valor" class="form-control mb-2"
       value="<?= $saque['valor'] ?>" required>

<label>Data</label>
<input type="date" name="data" class="form-control mb-2"
       value="<?= $saque['data'] ?>" required>

<label>Observação</label>
<input type="text" name="obs" class="form-control mb-3"
       value="<?= htmlspecialchars($saque['obs']) ?>">

<button class="btn btn-success btn-block">💾 Atualizar</button>
<a href="socio.php" class="btn btn-secondary btn-block mt-2">⬅️ Voltar</a>

</form>

<?php include "layout_footer.php"; ?>