<?php
require "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

/* ====== PROCESSA SAQUE DO SÓCIO ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao']==='saque_socio') {
    $socio = intval($_POST['socio']);
    $valor = floatval($_POST['valor']);
    $data  = $_POST['data'] ?? date('Y-m-d');
    $obs   = trim($_POST['obs'] ?? '');

    if ($valor > 0) {
        $stmt = $conn->prepare("INSERT INTO saques (socio_num, valor, data, obs) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idss", $socio, $valor, $data, $obs);
        $stmt->execute();
        $_SESSION['msg'] = "✅ Saque registrado com sucesso!";
    } else {
        $_SESSION['msg'] = "⚠️ Informe um valor válido.";
    }

    header("Location: socio.php");
    exit;
}

include "layout_header.php";

/* ===== SOMA TOTAL SÓCIOS ===== */
$tot = $conn->query("
SELECT 
COALESCE(SUM(socio1_valor),0) AS s1,
COALESCE(SUM(socio2_valor),0) AS s2
FROM servicos
")->fetch_assoc();

$s1_total = (float)$tot['s1'];
$s2_total = (float)$tot['s2'];

$s1_saque = (float)$conn->query("SELECT COALESCE(SUM(valor),0) AS v FROM saques WHERE socio_num = 1")->fetch_assoc()['v'];
$s2_saque = (float)$conn->query("SELECT COALESCE(SUM(valor),0) AS v FROM saques WHERE socio_num = 2")->fetch_assoc()['v'];

$s1_saldo = $s1_total - $s1_saque;
$s2_saldo = $s2_total - $s2_saque;

/* ===== RESERVA E DESLOCAMENTO ===== */
$reserva_total = (float)$conn->query("SELECT COALESCE(SUM(reserva_emergencia),0) t FROM servicos")->fetch_assoc()['t'];
$reserva_pago  = (float)$conn->query("SELECT COALESCE(SUM(valor),0) t FROM reserva_pagamentos")->fetch_assoc()['t'];
$reserva_saldo = $reserva_total - $reserva_pago;

$desloc_total = (float)$conn->query("SELECT COALESCE(SUM(desloc),0) t FROM servicos")->fetch_assoc()['t'];
$desloc_pago  = (float)$conn->query("SELECT COALESCE(SUM(valor),0) t FROM desloc_pagamentos")->fetch_assoc()['t'];
$desloc_saldo = $desloc_total - $desloc_pago;
?>

<style>
.card-socio {background:#fff;border-radius:8px;padding:15px;border-left:5px solid #007bff;box-shadow:0 2px 6px rgba(0,0,0,0.1);}
.card-box {background:#fff;border-radius:8px;padding:15px;box-shadow:0 2px 6px rgba(0,0,0,0.08);}
</style>

<h3>🤝 Controle de Sócios</h3>

<?php if(isset($_SESSION['msg'])): ?>
<div class="alert alert-info"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
<?php endif; ?>

<!-- SOCIOS -->
<div class="row">
    <div class="col-md-6 mb-3">
        <div class="card-socio">
            <h5>👤 <?= $SOCIO1_NOME ?></h5>
            <p><b>Total devido:</b> R$ <?= number_format($s1_total,2,',','.') ?></p>
            <p><b>Saques:</b> R$ <?= number_format($s1_saque,2,',','.') ?></p>
            <p><b>Saldo:</b> <span class="text-success">R$ <?= number_format($s1_saldo,2,',','.') ?></span></p>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="card-socio">
            <h5>👤 <?= $SOCIO2_NOME ?></h5>
            <p><b>Total devido:</b> R$ <?= number_format($s2_total,2,',','.') ?></p>
            <p><b>Saques:</b> R$ <?= number_format($s2_saque,2,',','.') ?></p>
            <p><b>Saldo:</b> <span class="text-success">R$ <?= number_format($s2_saldo,2,',','.') ?></span></p>
        </div>
    </div>
</div>

<!-- SAQUE SÓCIO -->
<div class="card-box mb-4">
<h4>📝 Registrar Saque</h4>

<form method="post" class="row g-3">
<input type="hidden" name="acao" value="saque_socio">

<div class="col-md-3">
    <label>Sócio</label>
    <select name="socio" class="form-control">
        <option value="1"><?= $SOCIO1_NOME ?></option>
        <option value="2"><?= $SOCIO2_NOME ?></option>
    </select>
</div>

<div class="col-md-2">
    <label>Valor</label>
    <input type="number" step="0.01" name="valor" class="form-control" required>
</div>

<div class="col-md-3">
    <label>Data</label>
    <input type="date" name="data" class="form-control" value="<?= date('Y-m-d') ?>">
</div>

<div class="col-md-3">
    <label>Obs</label>
    <input type="text" name="obs" class="form-control">
</div>

<div class="col-md-1 d-flex align-items-end">
    <button class="btn btn-success">Salvar</button>
</div>
</form>
</div>

<!-- HISTÓRICO SÓCIOS -->
<h4>📋 Histórico de Saques dos Sócios</h4>

<table class="table table-bordered table-hover">
<tr class="table-dark"><th>Data</th><th>Sócio</th><th>Valor</th><th>Obs</th><th>Ações</th></tr>

<?php
$saq = $conn->query("SELECT * FROM saques ORDER BY data DESC, id DESC");
while($s = $saq->fetch_assoc()):
?>
<tr>
<td><?= date('d/m/Y', strtotime($s['data'])) ?></td>
<td><?= $s['socio_num']==1 ? $SOCIO1_NOME : $SOCIO2_NOME ?></td>
<td>R$ <?= number_format($s['valor'],2,',','.') ?></td>
<td><?= htmlspecialchars($s['obs']) ?></td>
<td>
    <a href="saque_edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary">✏️</a>
    <a href="saque_delete.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir?')">🗑</a>
</td>
</tr>
<?php endwhile; ?>
</table>

<!-- RESERVA -->
<div class="card-box mb-4">
<h4>🏦 Reserva de Emergência</h4>
<p><b>Total acumulado:</b> R$ <?= number_format($reserva_saldo,2,',','.') ?></p>

<form method="post" action="pagar_reserva.php" class="row g-3 mb-3">
    <div class="col-md-3"><label>Valor</label><input type="number" step="0.01" name="valor" class="form-control" max="<?= $reserva_saldo ?>" required></div>
    <div class="col-md-3"><label>Data</label><input type="date" name="data" class="form-control" value="<?= date('Y-m-d') ?>"></div>
    <div class="col-md-4"><label>Obs</label><input type="text" name="obs" class="form-control"></div>
    <div class="col-md-2 d-flex align-items-end"><button class="btn btn-warning w-100">Pagar Reserva</button></div>
</form>

<table class="table table-sm table-bordered">
<tr class="table-light"><th>Data</th><th>Valor</th><th>Obs</th><th>Ações</th></tr>
<?php
$res = $conn->query("SELECT * FROM reserva_pagamentos ORDER BY data DESC");
while($r=$res->fetch_assoc()):
?>
<tr>
<td><?= date('d/m/Y',strtotime($r['data'])) ?></td>
<td>R$ <?= number_format($r['valor'],2,',','.') ?></td>
<td><?= htmlspecialchars($r['obs']) ?></td>
<td><a href="reserva_delete.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Excluir?')">🗑</a></td>
</tr>
<?php endwhile; ?>
</table>
</div>

<!-- DESLOCAMENTO -->
<div class="card-box mb-4">
<h4>🚗 Deslocamento</h4>
<p><b>Total acumulado:</b> R$ <?= number_format($desloc_saldo,2,',','.') ?></p>

<form method="post" action="pagar_desloc.php" class="row g-3 mb-3">
    <div class="col-md-3"><label>Valor</label><input type="number" step="0.01" name="valor" class="form-control" max="<?= $desloc_saldo ?>" required></div>
    <div class="col-md-3"><label>Data</label><input type="date" name="data" class="form-control" value="<?= date('Y-m-d') ?>"></div>
    <div class="col-md-4"><label>Obs</label><input type="text" name="obs" class="form-control"></div>
    <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Pagar Deslocamento</button></div>
</form>

<table class="table table-sm table-bordered">
<tr class="table-light"><th>Data</th><th>Valor</th><th>Obs</th><th>Ações</th></tr>
<?php
$des = $conn->query("SELECT * FROM desloc_pagamentos ORDER BY data DESC");
while($d=$des->fetch_assoc()):
?>
<tr>
<td><?= date('d/m/Y',strtotime($d['data'])) ?></td>
<td>R$ <?= number_format($d['valor'],2,',','.') ?></td>
<td><?= htmlspecialchars($d['obs']) ?></td>
<td><a href="desloc_delete.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Excluir?')">🗑</a></td>
</tr>
<?php endwhile; ?>
</table>
</div>

<?php include "layout_footer.php"; ?>