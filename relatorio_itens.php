<?php
require 'config.php';
if(!isset($_SESSION['user_id'])){ header('Location: login.php'); exit; }
include 'layout_header.php';

// período (mês atual)
$ini = $_GET['ini'] ?? date('Y-m-01');
$fim = $_GET['fim'] ?? date('Y-m-t');

$sql = "
SELECT produto_nome,
       SUM(quantidade) AS total_qtd,
       SUM(subtotal) AS total_custo
FROM servico_itens 
JOIN servicos ON servicos.id = servico_itens.servico_id
WHERE data BETWEEN ? AND ?
GROUP BY produto_nome
ORDER BY total_custo DESC
";

$stm = $conn->prepare($sql);
$stm->bind_param("ss", $ini, $fim);
$stm->execute();
$res = $stm->get_result();
?>

<h3>📦 Relatório de Consumo de Materiais</h3>

<form class="form-inline mb-3">
  <label class="mr-2">Início</label>
  <input type="date" name="ini" value="<?= $ini ?>" class="form-control mr-2">
  <label class="mr-2">Fim</label>
  <input type="date" name="fim" value="<?= $fim ?>" class="form-control mr-2">
  <button class="btn btn-primary">Filtrar</button>
</form>

<table class="table table-bordered table-striped">
<thead>
<tr>
  <th>Produto</th>
  <th class="text-right">Total Usado</th>
  <th class="text-right">Custo Total</th>
</tr>
</thead>
<tbody>
<?php while($r = $res->fetch_assoc()): ?>
<tr>
  <td><?= htmlspecialchars($r['produto_nome']) ?></td>
  <td class="text-right"><?= number_format($r['total_qtd'],2,',','.') ?></td>
  <td class="text-right">R$ <?= number_format($r['total_custo'],2,',','.') ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<a class="btn btn-secondary" href="dashboard.php">⬅ Voltar</a>

<?php include 'layout_footer.php'; ?>