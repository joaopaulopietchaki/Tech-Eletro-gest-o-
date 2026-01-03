<?php
require "config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: servicos.php");
    exit;
}

/* =========================
   FUNÇÃO MOEDA → FLOAT
========================= */
function moeda_to_float($v){
    if(!$v) return 0;
    $v = str_replace('.', '', $v);
    $v = str_replace(',', '.', $v);
    return floatval($v);
}

/* =========================
   DADOS PRINCIPAIS
========================= */
$id            = (int)($_POST['id'] ?? 0);
$cliente       = trim($_POST['cliente'] ?? '');
$servico       = trim($_POST['servico'] ?? '');
$valorRecebido = moeda_to_float($_POST['valor_recebido'] ?? 0);
$deslocamento  = moeda_to_float($_POST['deslocamento'] ?? 0);
$status        = $_POST['status'] ?? 'Em aberto';
$data          = $_POST['data'] ?? date('Y-m-d');

/* =========================
   ITENS / CUSTOS
========================= */
$item_desc  = $_POST['item_desc'] ?? [];
$item_valor = $_POST['item_valor'] ?? [];

$totalItens = 0;
$itensValidos = [];

for ($i = 0; $i < count($item_desc); $i++) {
    $desc = trim($item_desc[$i]);
    $val  = moeda_to_float($item_valor[$i] ?? 0);

    if ($desc !== '' && $val > 0) {
        $totalItens += $val;
        $itensValidos[] = [$desc, $val];
    }
}

/* =========================
   CÁLCULOS FINANCEIROS
========================= */
$custoTotal = $totalItens + $deslocamento;

/* Lucro bruto */
$lucroBruto = $valorRecebido - $custoTotal;
if ($lucroBruto < 0) $lucroBruto = 0;

/* Reserva = 10% do lucro bruto */
$reserva = $lucroBruto * 0.10;

/* Lucro líquido */
$lucroLiquido = $lucroBruto - $reserva;

/* Sócios */
$socio1 = $lucroLiquido / 2;
$socio2 = $lucroLiquido / 2;

/* =========================
   ATUALIZA SERVIÇO
========================= */
$sql = "
UPDATE servicos SET
    nome_cliente        = ?,
    servico_executado   = ?,
    valor_recebido      = ?,
    desloc              = ?,
    custo_total         = ?,
    reserva_emergencia  = ?,
    lucro               = ?,
    socio1_valor        = ?,
    socio2_valor        = ?,
    status_pagamento    = ?,
    data                = ?
WHERE id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ssdddddddssi",
    $cliente,
    $servico,
    $valorRecebido,
    $deslocamento,
    $custoTotal,
    $reserva,
    $lucroLiquido,
    $socio1,
    $socio2,
    $status,
    $data,
    $id
);
$stmt->execute();
$stmt->close();

/* =========================
   ATUALIZA ITENS
========================= */
/* Remove itens antigos */
$conn->query("DELETE FROM servico_itens WHERE servico_id = $id");

/* Insere novamente */
if (!empty($itensValidos)) {
    $stmtItem = $conn->prepare("
        INSERT INTO servico_itens (servico_id, produto_nome, valor_unit)
        VALUES (?, ?, ?)
    ");

    foreach ($itensValidos as $it) {
        $stmtItem->bind_param("isd", $id, $it[0], $it[1]);
        $stmtItem->execute();
    }
    $stmtItem->close();
}

/* =========================
   FINALIZA
========================= */
header("Location: servicos.php?ok=editado");
exit;