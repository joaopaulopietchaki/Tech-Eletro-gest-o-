
<?php
require "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include "layout_header.php";

// Logs recentes
$logs = $conn->query("SELECT * FROM logs ORDER BY data DESC LIMIT 50");

// Dados para gr��fico (exemplo: or�0�4amentos por m��s)
$dadosGrafico = $conn->query("
    SELECT DATE_FORMAT(data, '%m/%Y') AS mes, COUNT(*) AS total
    FROM orcamentos
    GROUP BY mes
    ORDER BY data ASC
");
$labels = [];
$valores = [];
while($g = $dadosGrafico->fetch_assoc()) {
    $labels[] = $g['mes'];
    $valores[] = $g['total'];
}
?>

<h3>�9�4 Relat��rios & Gr��ficos</h3>

<canvas id="graficoOrcamentos" style="max-height:300px;"></canvas>

<hr>

<h4>�0�8 �0�3ltimas A�0�4�0�1es no Sistema</h4>
<table class="table table-striped table-bordered mt-3">
<thead class="table-dark">
<tr>
    <th>ID</th>
    <th>A�0�4�0�0o</th>
    <th>Descri�0�4�0�0o</th>
    <th>Data</th>
</tr>
</thead>
<tbody>
<?php while($l = $logs->fetch_assoc()): ?>
<tr>
    <td><?= $l['id'] ?></td>
    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($l['acao']) ?></span></td>
    <td><?= htmlspecialchars($l['descricao']) ?></td>
    <td><?= date("d/m/Y H:i", strtotime($l['data'])) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('graficoOrcamentos').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Or�0�4amentos por m��s',
            data: <?= json_encode($valores) ?>,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: { y: { beginAtZero: true } }
    }
});
</script>

<?php include "layout_footer.php"; ?>