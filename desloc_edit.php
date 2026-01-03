<?php
require 'config.php';
if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$id = intval($_GET['id'] ?? 0);
$r = $conn->prepare("SELECT * FROM desloc_pagamentos WHERE id=?");
$r->bind_param("i",$id);
$r->execute();
$pag = $r->get_result()->fetch_assoc();
if(!$pag){ die("Registro não encontrado"); }

if($_POST){
    $valor = floatval($_POST['valor']);
    $data  = !empty($_POST['data']) ? $_POST['data'] : date('Y-m-d');
    $obs   = trim($_POST['obs'] ?? '');
    $u = $conn->prepare("UPDATE desloc_pagamentos SET data=?, valor=?, obs=? WHERE id=?");
    $u->bind_param("sdsi",$data,$valor,$obs,$id);
    $u->execute();
    $_SESSION['msg']="✅ Pagamento de Deslocamento atualizado!";
    header("Location: socio.php"); exit;
}

include 'layout_header.php';
?>
<h3>Editar Pagamento — Deslocamento</h3>
<form method="post">
  <label>Data</label>
  <input type="date" name="data" class="form-control mb-2" value="<?= htmlspecialchars($pag['data']) ?>">
  <label>Valor</label>
  <input type="number" step="0.01" name="valor" class="form-control mb-2" value="<?= (float)$pag['valor'] ?>">
  <label>Obs</label>
  <input type="text" name="obs" class="form-control mb-3" value="<?= htmlspecialchars($pag['obs']) ?>">
  <button class="btn btn-primary">Salvar</button>
  <a href="socio.php" class="btn btn-secondary">Cancelar</a>
</form>
<?php include 'layout_footer.php'; ?>