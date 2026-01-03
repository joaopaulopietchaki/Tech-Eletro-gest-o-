<?php
require "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$ids_param = $_GET['ids'] ?? '';
if (empty($ids_param)) {
    die("⚠️ Nenhum item selecionado");
}

// Parse dos IDs (formato: "tipo:id,tipo:id,...")
$ids_array = explode(',', $ids_param);
$servicos_ids = [];
$gastos_ids = [];

foreach ($ids_array as $item) {
    if (strpos($item, ':') !== false) {
        list($tipo, $id) = explode(':', $item, 2);
        $id = intval($id);
        
        if ($tipo === 'servico') {
            $servicos_ids[] = $id;
        } elseif ($tipo === 'gasto_extra') {
            $gastos_ids[] = $id;
        }
    }
}

// Busca serviços
$servicos = [];
if (!empty($servicos_ids)) {
    $ids_str = implode(',', $servicos_ids);
    $result = $conn->query("
        SELECT * FROM servicos 
        WHERE id IN ($ids_str) 
        ORDER BY data DESC
    ");
    while ($row = $result->fetch_assoc()) {
        $servicos[] = $row;
    }
}

// Busca gastos
$gastos = [];
if (!empty($gastos_ids)) {
    $ids_str = implode(',', $gastos_ids);
    $result = $conn->query("
        SELECT * FROM gastos_extras 
        WHERE id IN ($ids_str) 
        ORDER BY data DESC
    ");
    while ($row = $result->fetch_assoc()) {
        $gastos[] = $row;
    }
}

$total_servicos = count($servicos);
$total_gastos = count($gastos);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Relatório de Serviços e Gastos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
@media print {
    .no-print { display: none !important; }
    .page-break { page-break-after: always; }
}
body { font-size: 12px; }
.header-pdf { 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.card-servico {
    border-left: 4px solid #0d6efd;
    margin-bottom: 15px;
}
.card-gasto {
    border-left: 4px solid #dc3545;
    margin-bottom: 15px;
}
</style>
</head>
<body class="bg-light">

<div class="container my-4">

<!-- Botões de ação -->
<div class="no-print mb-3 d-flex gap-2">
    <button onclick="window.print()" class="btn btn-primary">
        🖨️ Imprimir
    </button>
    <button onclick="window.close()" class="btn btn-secondary">
        ❌ Fechar
    </button>
</div>

<!-- Cabeçalho -->
<div class="header-pdf text-center">
    <h3 class="mb-1">📊 RELATÓRIO DETALHADO</h3>
    <p class="mb-0">Serviços e Gastos Selecionados</p>
    <small>Gerado em: <?=date('d/m/Y H:i')?></small>
</div>

<!-- Resumo Geral -->
<div class="card mb-4">
<div class="card-body">
<h5>📈 Resumo Geral</h5>
<div class="row">
<div class="col-6">
    <p><b>Total de Serviços:</b> <?=$total_servicos?></p>
</div>
<div class="col-6">
    <p><b>Total de Gastos:</b> <?=$total_gastos?></p>
</div>
</div>
</div>
</div>

<!-- SERVIÇOS -->
<?php if (!empty($servicos)): ?>
<h4 class="mb-3">🛠️ SERVIÇOS</h4>

<?php foreach ($servicos as $serv): 
    // Busca itens do serviço
    $itens = [];
    $custo_itens = 0;
    $result_itens = $conn->query("SELECT * FROM servico_itens WHERE servico_id = {$serv['id']}");
    while ($item = $result_itens->fetch_assoc()) {
        $itens[] = $item;
        $custo_itens += (float)$item['valor_unit'];
    }
    
    $valorRecebido = (float)$serv['valor_recebido'];
    $deslocamento  = (float)$serv['desloc'];
    $custoTotal    = $custo_itens + $deslocamento;
    $lucroBruto    = max($valorRecebido - $custoTotal, 0);
    $reserva       = $lucroBruto * 0.10;
    $lucroLiquido  = $lucroBruto - $reserva;
    $socio         = $lucroLiquido / 2;
?>

<div class="card card-servico">
<div class="card-body">

<div class="row mb-2">
<div class="col-8">
    <h6 class="mb-1">👤 <?=htmlspecialchars($serv['nome_cliente'])?></h6>
    <p class="mb-0 text-muted"><?=htmlspecialchars($serv['servico_executado'])?></p>
</div>
<div class="col-4 text-end">
    <p class="mb-0"><b>Data:</b> <?=date('d/m/Y', strtotime($serv['data']))?></p>
    <span class="badge <?=$serv['status_pagamento']=='Pago total'?'bg-success':'bg-warning'?>">
        <?=$serv['status_pagamento']?>
    </span>
</div>
</div>

<hr class="my-2">

<!-- Valores -->
<div class="row small">
<div class="col-6">
    <p class="mb-1">💰 <b>Valor Recebido:</b> R$ <?=number_format($valorRecebido,2,',','.')?></p>
    <p class="mb-1">🚗 <b>Deslocamento:</b> R$ <?=number_format($deslocamento,2,',','.')?></p>
</div>
<div class="col-6">
    <p class="mb-1">📦 <b>Custo Itens:</b> R$ <?=number_format($custo_itens,2,',','.')?></p>
    <p class="mb-1">🏦 <b>Reserva:</b> R$ <?=number_format($reserva,2,',','.')?></p>
</div>
</div>

<!-- Itens -->
<?php if (!empty($itens)): ?>
<hr class="my-2">
<p class="mb-1"><b>📦 Itens do Serviço:</b></p>
<table class="table table-sm table-bordered mb-0">
<thead class="table-light">
<tr>
    <th>Descrição</th>
    <th width="120" class="text-end">Valor</th>
</tr>
</thead>
<tbody>
<?php foreach ($itens as $it): ?>
<tr>
    <td><?=htmlspecialchars($it['produto_nome'])?></td>
    <td class="text-end">R$ <?=number_format($it['valor_unit'],2,',','.')?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<!-- Resumo Financeiro -->
<div class="mt-3 p-2 bg-light rounded">
<div class="row">
<div class="col-6">
    <p class="mb-1"><b>💵 Lucro Líquido:</b></p>
    <h5 class="text-success mb-0">R$ <?=number_format($lucroLiquido,2,',','.')?></h5>
</div>
<div class="col-6">
    <p class="mb-1"><b>👥 Cada Sócio:</b></p>
    <h5 class="text-primary mb-0">R$ <?=number_format($socio,2,',','.')?></h5>
</div>
</div>
</div>

</div>
</div>

<?php endforeach; ?>
<?php endif; ?>

<!-- GASTOS EXTRAS -->
<?php if (!empty($gastos)): ?>
<div class="page-break"></div>
<h4 class="mb-3 mt-4">💸 GASTOS EXTRAS</h4>

<?php 
$total_gastos_valor = 0;
foreach ($gastos as $gasto): 
    $total_gastos_valor += (float)$gasto['valor'];
?>

<div class="card card-gasto">
<div class="card-body">

<div class="row">
<div class="col-8">
    <h6 class="mb-1">💳 <?=htmlspecialchars($gasto['descricao'])?></h6>
    <p class="mb-0 text-muted small">Data: <?=date('d/m/Y', strtotime($gasto['data']))?></p>
</div>
<div class="col-4 text-end">
    <h5 class="text-danger mb-1">R$ <?=number_format($gasto['valor'],2,',','.')?></h5>
    <span class="badge <?=$gasto['pago']==1?'bg-success':'bg-warning'?>">
        <?=$gasto['pago']==1?'Pago':'Em aberto'?>
    </span>
</div>
</div>

</div>
</div>

<?php endforeach; ?>

<!-- Total de Gastos -->
<div class="card bg-danger text-white">
<div class="card-body py-2">
<div class="d-flex justify-content-between align-items-center">
    <h6 class="mb-0">💸 TOTAL DE GASTOS:</h6>
    <h5 class="mb-0">R$ <?=number_format($total_gastos_valor,2,',','.')?></h5>
</div>
</div>
</div>

<?php endif; ?>

<!-- RESUMO FINANCEIRO GERAL -->
<div class="page-break"></div>
<div class="mt-4">
<h4 class="mb-3 text-center">📊 RESUMO FINANCEIRO CONSOLIDADO</h4>

<?php
// Cálculos totais
$total_recebido = 0;
$total_itens = 0;
$total_deslocamento = 0;
$total_reserva = 0;

foreach ($servicos as $s) {
    $total_recebido += (float)$s['valor_recebido'];
    $total_deslocamento += (float)$s['desloc'];
    $total_reserva += (float)$s['reserva_emergencia'];
    
    // Soma itens de cada serviço
    $result = $conn->query("SELECT SUM(valor_unit) as total FROM servico_itens WHERE servico_id = {$s['id']}");
    $row = $result->fetch_assoc();
    $total_itens += (float)($row['total'] ?? 0);
}

// Total de gastos extras já calculado
$custo_total_geral = $total_itens + $total_deslocamento;
$lucro_bruto_geral = $total_recebido - $custo_total_geral;
$lucro_liquido_geral = $lucro_bruto_geral - $total_reserva - $total_gastos_valor;
$valor_por_socio = $lucro_liquido_geral / 2;
?>

<!-- Cards de Resumo -->
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-success">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">💰 TOTAL RECEBIDO</h6>
                <h4 class="text-success mb-0">R$ <?=number_format($total_recebido,2,',','.')?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-primary">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">📦 TOTAL ITENS/PEÇAS</h6>
                <h4 class="text-primary mb-0">R$ <?=number_format($total_itens,2,',','.')?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-info">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">🚗 TOTAL DESLOCAMENTO</h6>
                <h4 class="text-info mb-0">R$ <?=number_format($total_deslocamento,2,',','.')?></h4>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-warning">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">🏦 TOTAL RESERVA EMERGÊNCIA</h6>
                <h4 class="text-warning mb-0">R$ <?=number_format($total_reserva,2,',','.')?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-danger">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">💸 TOTAL GASTOS EXTRAS</h6>
                <h4 class="text-danger mb-0">R$ <?=number_format($total_gastos_valor,2,',','.')?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-secondary">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">📊 CUSTO TOTAL</h6>
                <h4 class="text-secondary mb-0">R$ <?=number_format($custo_total_geral,2,',','.')?></h4>
            </div>
        </div>
    </div>
</div>

<!-- Resultado Final -->
<div class="card bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="card-body text-white">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-2">💵 LUCRO LÍQUIDO TOTAL</h5>
                <h3 class="mb-0">R$ <?=number_format($lucro_liquido_geral,2,',','.')?></h3>
            </div>
            <div class="col-md-6 text-end">
                <h5 class="mb-2">👥 VALOR POR SÓCIO</h5>
                <h3 class="mb-0">R$ <?=number_format($valor_por_socio,2,',','.')?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Tabela Detalhada -->
<div class="card mt-3">
    <div class="card-body">
        <h6 class="mb-3">📋 Detalhamento Financeiro</h6>
        <table class="table table-bordered mb-0">
            <tbody>
                <tr>
                    <th width="50%">Total Recebido</th>
                    <td class="text-end">R$ <?=number_format($total_recebido,2,',','.')?></td>
                </tr>
                <tr>
                    <th>(-) Custo de Itens/Peças</th>
                    <td class="text-end text-danger">R$ <?=number_format($total_itens,2,',','.')?></td>
                </tr>
                <tr>
                    <th>(-) Deslocamento</th>
                    <td class="text-end text-danger">R$ <?=number_format($total_deslocamento,2,',','.')?></td>
                </tr>
                <tr class="table-light">
                    <th>(=) Lucro Bruto</th>
                    <td class="text-end"><b>R$ <?=number_format($lucro_bruto_geral,2,',','.')?></b></td>
                </tr>
                <tr>
                    <th>(-) Reserva de Emergência (10%)</th>
                    <td class="text-end text-danger">R$ <?=number_format($total_reserva,2,',','.')?></td>
                </tr>
                <tr>
                    <th>(-) Gastos Extras</th>
                    <td class="text-end text-danger">R$ <?=number_format($total_gastos_valor,2,',','.')?></td>
                </tr>
                <tr class="table-success">
                    <th><b>(=) LUCRO LÍQUIDO TOTAL</b></th>
                    <td class="text-end"><h5 class="mb-0 text-success">R$ <?=number_format($lucro_liquido_geral,2,',','.')?></h5></td>
                </tr>
                <tr class="table-primary">
                    <th><b>VALOR POR SÓCIO (50%)</b></th>
                    <td class="text-end"><h5 class="mb-0 text-primary">R$ <?=number_format($valor_por_socio,2,',','.')?></h5></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

</div>

<!-- Rodapé -->
<div class="mt-4 text-center text-muted small">
<hr>
<p class="mb-0">Sistema de Gestão - Tech Eletro</p>
<p class="mb-0">Relatório gerado automaticamente em <?=date('d/m/Y \à\s H:i:s')?></p>
</div>

</div>

</body>
</html>