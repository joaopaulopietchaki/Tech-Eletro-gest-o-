<?php
require "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: servicos.php");
    exit;
}

/* =========================
   BUSCA SERVIÇO / GASTO
========================= */
$serv = $conn->query("SELECT * FROM servicos WHERE id = $id")->fetch_assoc();
if (!$serv) {
    header("Location: servicos.php");
    exit;
}

/* =========================
   BUSCA ITENS (SE FOR SERVIÇO)
========================= */
$itens = [];
if ($serv['tipo'] === 'servico') {
    $q = $conn->query("SELECT * FROM servico_itens WHERE servico_id = $id");
    while ($r = $q->fetch_assoc()) {
        $itens[] = $r;
    }
}

/* =========================
   VARIÁVEIS FINANCEIRAS
========================= */
$valorRecebido = (float)$serv['valor_recebido'];
$deslocamento  = (float)$serv['desloc'];
$custoItens    = 0;

foreach ($itens as $i) {
    $custoItens += (float)$i['valor_unit'];
}

$custoTotal   = $custoItens + $deslocamento;
$lucroBruto   = max($valorRecebido - $custoTotal, 0);
$reserva      = $lucroBruto * 0.10;
$lucroLiquido = $lucroBruto - $reserva;
$socio1       = $lucroLiquido / 2;
$socio2       = $lucroLiquido / 2;

include 'layout_header.php';
?>

<div class="container mt-3 mb-5">

<h4 class="mb-3">
👁️ Visualização —
<?= $serv['tipo'] === 'servico' ? 'Serviço' : 'Gasto Extra' ?>
</h4>

<!-- DADOS PRINCIPAIS -->
<div class="card mb-3">
<div class="card-body">

<div class="row g-3">
<div class="col-md-6">
<b>Cliente / Descrição</b><br>
<?= htmlspecialchars($serv['nome_cliente'] ?? '-') ?>
</div>

<div class="col-md-6">
<b>Serviço Executado</b><br>
<?= htmlspecialchars($serv['servico_executado'] ?? '-') ?>
</div>

<div class="col-md-3">
<b>Data</b><br>
<?= date('d/m/Y', strtotime($serv['data'])) ?>
</div>

<div class="col-md-3">
<b>Status</b><br>
<span class="badge <?= $serv['status_pagamento']=='Pago total'?'bg-success':'bg-warning' ?>">
<?= $serv['status_pagamento'] ?>
</span>
</div>

<div class="col-md-3">
<b>Valor Recebido</b><br>
R$ <?= number_format($valorRecebido,2,',','.') ?>
</div>

<div class="col-md-3">
<b>Deslocamento</b><br>
R$ <?= number_format($deslocamento,2,',','.') ?>
</div>
</div>

</div>
</div>

<!-- ITENS / CUSTOS -->
<?php if ($serv['tipo'] === 'servico'): ?>
<div class="card mb-3">
<div class="card-body">

<h5 class="mb-3">📦 Custos / Itens</h5>

<table class="table table-bordered">
<thead class="table-light">
<tr>
<th>Descrição</th>
<th width="180" class="text-end">Valor</th>
</tr>
</thead>
<tbody>
<?php if ($itens): foreach ($itens as $i): ?>
<tr>
<td><?= htmlspecialchars($i['produto_nome']) ?></td>
<td class="text-end">R$ <?= number_format($i['valor_unit'],2,',','.') ?></td>
</tr>
<?php endforeach; else: ?>
<tr>
<td colspan="2" class="text-center text-muted">Nenhum item informado</td>
</tr>
<?php endif; ?>
</tbody>
<tfoot class="table-secondary">
<tr>
<th>Total Itens</th>
<th class="text-end">R$ <?= number_format($custoItens,2,',','.') ?></th>
</tr>
</tfoot>
</table>

</div>
</div>
<?php endif; ?>

<!-- RESUMO FINANCEIRO -->
<div class="card">
<div class="card-body">

<h5 class="mb-3">📊 Resumo Financeiro</h5>

<table class="table table-bordered">
<tbody>
<tr>
<th>Custo Total (itens + deslocamento)</th>
<td class="text-end">R$ <?= number_format($custoTotal,2,',','.') ?></td>
</tr>
<tr>
<th>Lucro Bruto</th>
<td class="text-end">R$ <?= number_format($lucroBruto,2,',','.') ?></td>
</tr>
<tr>
<th>Reserva (10%)</th>
<td class="text-end">R$ <?= number_format($reserva,2,',','.') ?></td>
</tr>
<tr class="table-success">
<th>Lucro Líquido</th>
<td class="text-end"><b>R$ <?= number_format($lucroLiquido,2,',','.') ?></b></td>
</tr>
<tr>
<th>Valor por Sócio</th>
<td class="text-end"><b>R$ <?= number_format($socio1,2,',','.') ?></b></td>
</tr>
</tbody>
</table>

</div>
</div>

<!-- AÇÕES -->
<div class="mt-4 d-flex gap-2">
<a href="servico_edit.php?id=<?= $id ?>" class="btn btn-warning w-50">✏️ Editar</a>
<a href="servicos.php" class="btn btn-secondary w-50">⬅ Voltar</a>
</div>

</div>

<?php include 'layout_footer.php'; ?>