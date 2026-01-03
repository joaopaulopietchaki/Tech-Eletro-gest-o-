<?php
require_once "config.php";
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
include "layout_header.php";

/* ========= Período ========= */
$ini = $_GET['ini'] ?? date('Y-01-01');
$fim = $_GET['fim'] ?? date('Y-12-31');

/* ========= Linha: Receita × Custos × Lucro por mês ========= */
$sqlMes = $conn->prepare("
  SELECT DATE_FORMAT(data,'%Y-%m') as mes,
         COALESCE(SUM(valor_recebido),0) as rec,
         COALESCE(SUM(custo_total),0)    as cus,
         COALESCE(SUM(lucro),0)          as luc,
         COALESCE(SUM(reserva_emergencia),0) as res
  FROM servicos
  WHERE data BETWEEN ? AND ?
  GROUP BY mes
  ORDER BY mes
");
$sqlMes->bind_param("ss", $ini, $fim);
$sqlMes->execute();
$rsMes = $sqlMes->get_result();

$mesLabels = $serieRec = $serieCus = $serieLuc = $serieRes = [];
$acumRes = 0; $serieResAcum = [];
while($r = $rsMes->fetch_assoc()){
  $mesLabels[] = $r['mes'];
  $serieRec[]  = (float)$r['rec'];
  $serieCus[]  = (float)$r['cus'];
  $serieLuc[]  = (float)$r['luc'];
  $acumRes    += (float)$r['res'];
  $serieResAcum[] = $acumRes;
}

/* ========= Barras: Top 10 clientes por receita ========= */
$sqlTopCli = $conn->prepare("
  SELECT nome_cliente, COALESCE(SUM(valor_recebido),0) as total
  FROM servicos
  WHERE data BETWEEN ? AND ?
  GROUP BY nome_cliente
  ORDER BY total DESC
  LIMIT 10
");
$sqlTopCli->bind_param("ss", $ini, $fim);
$sqlTopCli->execute();
$rsCli = $sqlTopCli->get_result();

$topCliLabels = $topCliValues = [];
while($r = $rsCli->fetch_assoc()){
  $topCliLabels[] = $r['nome_cliente'] ?: "—";
  $topCliValues[] = (float)$r['total'];
}

/* ========= Pizza: Receita por serviço ========= */
$sqlServ = $conn->prepare("
  SELECT servico_executado as serv, COALESCE(SUM(valor_recebido),0) as total
  FROM servicos
  WHERE data BETWEEN ? AND ?
  GROUP BY servico_executado
  ORDER BY total DESC
  LIMIT 8
");
$sqlServ->bind_param("ss", $ini, $fim);
$sqlServ->execute();
$rsServ = $sqlServ->get_result();

$servLabels = $servValues = [];
while($r = $rsServ->fetch_assoc()){
  $servLabels[] = $r['serv'] ?: '—';
  $servValues[] = (float)$r['total'];
}

/* ========= Pizza: Estrutura de custos (F1..F5 + Deslocamento) ========= */
$sqlCustos = $conn->prepare("
  SELECT
    COALESCE(SUM(fornecedor1),0) f1,
    COALESCE(SUM(fornecedor2),0) f2,
    COALESCE(SUM(fornecedor3),0) f3,
    COALESCE(SUM(fornecedor4),0) f4,
    COALESCE(SUM(fornecedor5),0) f5,
    COALESCE(SUM(deslocamento),0) desloc
  FROM servicos
  WHERE data BETWEEN ? AND ?
");
$sqlCustos->bind_param("ss", $ini, $fim);
$sqlCustos->execute();
$totCustos = $sqlCustos->get_result()->fetch_assoc();

$custoLabels = ["Fornecedor 1","Fornecedor 2","Fornecedor 3","Fornecedor 4","Fornecedor 5","Deslocamento"];
$custoValues = [
  (float)$totCustos['f1'],
  (float)$totCustos['f2'],
  (float)$totCustos['f3'],
  (float)$totCustos['f4'],
  (float)$totCustos['f5'],
  (float)$totCustos['desloc'],
];
?>

<h3>📈 Gráficos — Visão avançada</h3>

<form class="form-inline mb-3">
  <label class="mr-2">Início</label>
  <input type="date" name="ini" class="form-control mr-3" value="<?= htmlspecialchars($ini) ?>">
  <label class="mr-2">Fim</label>
  <input type="date" name="fim" class="form-control mr-3" value="<?= htmlspecialchars($fim) ?>">
  <button class="btn btn-primary">Aplicar</button>
</form>

<div class="row">
  <div class="col-lg-8 mb-4">
    <div class="card p-3 shadow-sm">
      <h6>Receita × Custos × Lucro (mensal)</h6>
      <canvas id="chartLinha" height="120"></canvas>
    </div>
  </div>

  <div class="col-lg-4 mb-4">
    <div class="card p-3 shadow-sm">
      <h6>Reserva acumulada (mês a mês)</h6>
      <canvas id="chartReserva" height="120"></canvas>
    </div>
  </div>

  <div class="col-lg-6 mb-4">
    <div class="card p-3 shadow-sm">
      <h6>Top 10 clientes por receita (período)</h6>
      <canvas id="chartTopCli" height="140"></canvas>
    </div>
  </div>

  <div class="col-lg-6 mb-4">
    <div class="card p-3 shadow-sm">
      <h6>Receita por serviço (TOP 8)</h6>
      <canvas id="chartServ" height="140"></canvas>
    </div>
  </div>

  <div class="col-lg-6 mb-4">
    <div class="card p-3 shadow-sm">
      <h6>Estrutura de custos</h6>
      <canvas id="chartCustos" height="140"></canvas>
    </div>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Dados do PHP → JS
const mesLabels   = <?= json_encode($mesLabels) ?>;
const serieRec    = <?= json_encode($serieRec) ?>;
const serieCus    = <?= json_encode($serieCus) ?>;
const serieLuc    = <?= json_encode($serieLuc) ?>;
const serieResAcum= <?= json_encode($serieResAcum) ?>;

const topCliLabels = <?= json_encode($topCliLabels) ?>;
const topCliValues = <?= json_encode($topCliValues) ?>;

const servLabels = <?= json_encode($servLabels) ?>;
const servValues = <?= json_encode($servValues) ?>;

const custoLabels = <?= json_encode($custoLabels) ?>;
const custoValues = <?= json_encode($custoValues) ?>;

// Linha: Receita × Custos × Lucro
new Chart(document.getElementById('chartLinha'), {
  type: 'line',
  data: {
    labels: mesLabels,
    datasets: [
      { label: 'Receita (R$)', data: serieRec, tension: .2 },
      { label: 'Custos (R$)',  data: serieCus, tension: .2 },
      { label: 'Lucro (R$)',   data: serieLuc, tension: .2 }
    ]
  },
  options: {
    responsive: true,
    plugins: { legend: { position: 'bottom' } },
    scales: { y: { beginAtZero: true } }
  }
});

// Linha: Reserva acumulada
new Chart(document.getElementById('chartReserva'), {
  type: 'line',
  data: {
    labels: mesLabels,
    datasets: [{ label: 'Reserva acumulada (R$)', data: serieResAcum, tension:.2 }]
  },
  options: {
    responsive: true,
    plugins: { legend: { position: 'bottom' } },
    scales: { y: { beginAtZero: true } }
  }
});

// Barras: Top clientes
new Chart(document.getElementById('chartTopCli'), {
  type: 'bar',
  data: {
    labels: topCliLabels,
    datasets: [{ label: 'Receita (R$)', data: topCliValues }]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { x: { beginAtZero: true } }
  }
});

// Pizza: Receita por serviço
new Chart(document.getElementById('chartServ'), {
  type: 'doughnut',
  data: {
    labels: servLabels,
    datasets: [{ data: servValues }]
  },
  options: {
    responsive: true,
    plugins: { legend: { position: 'bottom' } }
  }
});

// Pizza: Estrutura de custos
new Chart(document.getElementById('chartCustos'), {
  type: 'pie',
  data: {
    labels: custoLabels,
    datasets: [{ data: custoValues }]
  },
  options: {
    responsive: true,
    plugins: { legend: { position: 'bottom' } }
  }
});
</script>

<?php include "layout_footer.php"; ?>