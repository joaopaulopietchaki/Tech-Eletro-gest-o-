<?php
require 'config.php';
if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

include 'layout_header.php';

$ini = $_GET['inicio'] ?? date('Y-m-01');
$fim = $_GET['fim'] ?? date('Y-m-t');

$q = $conn->prepare("SELECT * FROM saques WHERE data BETWEEN ? AND ? ORDER BY data DESC");
$q->bind_param("ss", $ini, $fim);
$q->execute();
$res = $q->get_result();
?>

<h3>📅 Histórico de Saques</h3>

<form method="get" class="row mb-3">
    <div class="col-md-4">
        <label>Início</label>
        <input type="date" name="inicio" class="form-control" value="<?= $ini ?>">
    </div>
    <div class="col-md-4">
        <label>Fim</label>
        <input type="date" name="fim" class="form-control" value="<?= $fim ?>">
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <button class="btn btn-primary btn-block">Filtrar</button>
    </div>
</form>

<table class="table table-striped">
<thead>
<tr>
<th>Data</th>
<th>Sócio</th>
<th>Valor</th>
<th>Obs</th>
</tr>
</thead>
<tbody>

<?php while($s = $res->fetch_assoc()): ?>
<tr>
<td><?= date('d/m/Y', strtotime($s['data'])) ?></td>
<td><?= $s['socio_num']==1 ? 'Sócio 1' : 'Sócio 2' ?></td>
<td>R$ <?= number_format($s['valor'],2,',','.') ?></td>
<td><?= htmlspecialchars($s['obs']) ?></td>
</tr>
<?php endwhile; ?>

</tbody>
</table>

<a href="socios.php" class="btn btn-secondary">Voltar</a>

<?php include 'layout_footer.php'; ?>
