<?php
require "config.php";
if (session_status()===PHP_SESSION_NONE) session_start();
include "layout_header.php";

$hoje = date('Y-m-d');
$alerta = date('Y-m-d', strtotime('+7 days'));

$vig = $conn->query("SELECT * FROM servicos WHERE garantia_fim IS NOT NULL AND garantia_fim >= '$hoje' ORDER BY garantia_fim ASC");
$prox = $conn->query("SELECT * FROM servicos WHERE garantia_fim BETWEEN '$hoje' AND '$alerta' ORDER BY garantia_fim ASC");
$ven = $conn->query("SELECT * FROM servicos WHERE garantia_fim IS NOT NULL AND garantia_fim < '$hoje' ORDER BY garantia_fim DESC");
?>

<h3>🛡️ Garantias</h3>

<h5>🔔 A vencer (próximos 7 dias)</h5>
<table class="table table-sm table-bordered">
<tr class="table-warning"><th>Cliente</th><th>Serviço</th><th>Vence em</th><th>Ações</th></tr>
<?php if($prox->num_rows==0): ?><tr><td colspan="4" class="text-center text-muted">Nada por enquanto</td></tr><?php endif; ?>
<?php while($s=$prox->fetch_assoc()): ?>
<tr>
  <td><?= htmlspecialchars($s['nome_cliente']) ?></td>
  <td><?= htmlspecialchars($s['servico_executado']) ?></td>
  <td><?= date('d/m/Y',strtotime($s['garantia_fim'])) ?></td>
  <td><a class="btn btn-sm btn-info" href="servico_view.php?id=<?= $s['id'] ?>">Ver OS</a></td>
</tr>
<?php endwhile; ?>
</table>

<h5>✅ Em vigor</h5>
<table class="table table-sm table-bordered">
<tr class="table-success"><th>Cliente</th><th>Serviço</th><th>Vence em</th><th>Ações</th></tr>
<?php if($vig->num_rows==0): ?><tr><td colspan="4" class="text-center text-muted">Nada por enquanto</td></tr><?php endif; ?>
<?php while($s=$vig->fetch_assoc()): ?>
<tr>
  <td><?= htmlspecialchars($s['nome_cliente']) ?></td>
  <td><?= htmlspecialchars($s['servico_executado']) ?></td>
  <td><?= date('d/m/Y',strtotime($s['garantia_fim'])) ?></td>
  <td><a class="btn btn-sm btn-info" href="servico_view.php?id=<?= $s['id'] ?>">Ver OS</a></td>
</tr>
<?php endwhile; ?>
</table>

<h5>❌ Vencidas</h5>
<table class="table table-sm table-bordered">
<tr class="table-danger"><th>Cliente</th><th>Serviço</th><th>Venceu em</th><th>Ações</th></tr>
<?php if($ven->num_rows==0): ?><tr><td colspan="4" class="text-center text-muted">Nada por enquanto</td></tr><?php endif; ?>
<?php while($s=$ven->fetch_assoc()): ?>
<tr>
  <td><?= htmlspecialchars($s['nome_cliente']) ?></td>
  <td><?= htmlspecialchars($s['servico_executado']) ?></td>
  <td><?= date('d/m/Y',strtotime($s['garantia_fim'])) ?></td>
  <td><a class="btn btn-sm btn-info" href="servico_view.php?id=<?= $s['id'] ?>">Ver OS</a></td>
</tr>
<?php endwhile; ?>
</table>

<?php include "layout_footer.php"; ?>