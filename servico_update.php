<?php
require 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

function br_to_float($v){
    $v = str_replace(['.', ' '], '', $v);
    $v = str_replace(',', '.', $v);
    return floatval($v);
}

$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['msg'] = '⚠️ ID inválido';
    header("Location: servicos.php");
    exit;
}

$cliente   = trim($_POST['cliente'] ?? '');
$servico   = trim($_POST['servico'] ?? '');
$valor     = br_to_float($_POST['valor_recebido'] ?? '0,00');
$desloc    = br_to_float($_POST['deslocamento'] ?? '0,00');
$status    = $_POST['status'] ?? 'Em aberto';
$data      = $_POST['data'] ?? date('Y-m-d');

if ($cliente === '' || $servico === '') {
    $_SESSION['msg'] = '⚠️ Cliente e serviço são obrigatórios';
    header("Location: servico_edit.php?id=$id");
    exit;
}

// Calcula custos dos itens
$item_ids = $_POST['item_id'] ?? [];
$item_desc = $_POST['item_desc'] ?? [];
$item_valor = $_POST['item_valor'] ?? [];
$custo_itens = 0;

foreach ($item_valor as $v) {
    $custo_itens += br_to_float($v);
}

// Cálculos financeiros
$custo_total   = $custo_itens + $desloc;
$lucro_bruto   = max($valor - $custo_total, 0);
$reserva       = $lucro_bruto * 0.10;
$lucro_liquido = $lucro_bruto - $reserva;
$socio1        = $lucro_liquido / 2;
$socio2        = $lucro_liquido / 2;

// Atualiza serviço
$stmt = $conn->prepare("
    UPDATE servicos SET
        nome_cliente = ?,
        servico_executado = ?,
        valor_recebido = ?,
        desloc = ?,
        custo_total = ?,
        lucro = ?,
        reserva_emergencia = ?,
        socio1_valor = ?,
        socio2_valor = ?,
        status_pagamento = ?,
        data = ?
    WHERE id = ?
");

$stmt->bind_param(
    'ssdddddddssi',
    $cliente,
    $servico,
    $valor,
    $desloc,
    $custo_total,
    $lucro_liquido,
    $reserva,
    $socio1,
    $socio2,
    $status,
    $data,
    $id
);

if ($stmt->execute()) {
    
    // Atualiza/Insere itens
    for ($i = 0; $i < count($item_desc); $i++) {
        $item_id = intval($item_ids[$i] ?? 0);
        $desc = trim($item_desc[$i] ?? '');
        $valor_item = br_to_float($item_valor[$i] ?? '0,00');
        
        if ($desc === '') continue;
        
        if ($item_id > 0) {
            // Atualiza item existente
            $stmt_item = $conn->prepare("
                UPDATE servico_itens 
                SET produto_nome = ?, valor_unit = ?
                WHERE id = ? AND servico_id = ?
            ");
            $stmt_item->bind_param('sdii', $desc, $valor_item, $item_id, $id);
            $stmt_item->execute();
            $stmt_item->close();
        } else {
            // Insere novo item
            $stmt_item = $conn->prepare("
                INSERT INTO servico_itens (servico_id, produto_nome, valor_unit)
                VALUES (?, ?, ?)
            ");
            $stmt_item->bind_param('isd', $id, $desc, $valor_item);
            $stmt_item->execute();
            $stmt_item->close();
        }
    }
    
    $_SESSION['msg'] = '✅ Serviço atualizado com sucesso!';
} else {
    $_SESSION['msg'] = '❌ Erro ao atualizar serviço: ' . $stmt->error;
}

header("Location: servicos.php");
exit;