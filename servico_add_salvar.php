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

$tipo = $_POST['tipo'] ?? 'servico';

/* =====================================================
   GASTO EXTRA
===================================================== */
if ($tipo === 'gasto_extra') {

    $descricao = trim($_POST['extra_desc'] ?? '');
    $valor     = br_to_float($_POST['extra_valor'] ?? '0,00');
    $data      = $_POST['extra_data'] ?? date('Y-m-d');

    if ($descricao === '' || $valor <= 0) {
        $_SESSION['msg'] = '⚠️ Descrição ou valor do gasto inválido';
        header("Location: servico_add.php");
        exit;
    }

    // Insere na tabela gastos_extras
    $stmt = $conn->prepare("
        INSERT INTO gastos_extras (descricao, valor, data, pago)
        VALUES (?, ?, ?, 0)
    ");
    $stmt->bind_param('sds', $descricao, $valor, $data);
    
    if ($stmt->execute()) {
        $_SESSION['msg'] = '✅ Gasto extra cadastrado com sucesso!';
    } else {
        $_SESSION['msg'] = '❌ Erro ao cadastrar gasto: ' . $stmt->error;
    }

    header("Location: servicos.php");
    exit;
}

/* =====================================================
   SERVIÇO
===================================================== */
$cliente   = trim($_POST['cliente'] ?? '');
$servico   = trim($_POST['servico'] ?? '');
$valor     = br_to_float($_POST['valor'] ?? '0,00');
$desloc    = br_to_float($_POST['deslocamento'] ?? '0,00');
$status    = $_POST['status'] ?? 'Em aberto';
$data      = $_POST['data'] ?? date('Y-m-d');

// Validações
if ($cliente === '' || $servico === '') {
    $_SESSION['msg'] = '⚠️ Cliente e serviço são obrigatórios';
    header("Location: servico_add.php");
    exit;
}

// Calcula custos dos itens
$produtos = $_POST['produto'] ?? [];
$valores_itens = $_POST['valor_item'] ?? [];
$custo_itens = 0;

foreach ($valores_itens as $v) {
    $custo_itens += br_to_float($v);
}

// Cálculos financeiros
$custo_total   = $custo_itens + $desloc;
$lucro_bruto   = max($valor - $custo_total, 0);
$reserva       = $lucro_bruto * 0.10;
$lucro_liquido = $lucro_bruto - $reserva;
$socio1        = $lucro_liquido / 2;
$socio2        = $lucro_liquido / 2;

// Insere serviço
$stmt = $conn->prepare("
    INSERT INTO servicos (
        tipo,
        nome_cliente,
        servico_executado,
        valor_recebido,
        desloc,
        custo_total,
        lucro,
        reserva_emergencia,
        socio1_valor,
        socio2_valor,
        status_pagamento,
        data
    ) VALUES (
        'servico', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )
");

$stmt->bind_param(
    'ssdddddddss',
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
    $data
);

if ($stmt->execute()) {
    $servico_id = $stmt->insert_id;
    
    // Insere itens do serviço
    if (count($produtos) > 0) {
        $stmt_item = $conn->prepare("
            INSERT INTO servico_itens (servico_id, produto_nome, valor_unit)
            VALUES (?, ?, ?)
        ");
        
        for ($i = 0; $i < count($produtos); $i++) {
            if (trim($produtos[$i]) !== '') {
                $valor_item = br_to_float($valores_itens[$i] ?? '0,00');
                $stmt_item->bind_param('isd', $servico_id, $produtos[$i], $valor_item);
                $stmt_item->execute();
            }
        }
        $stmt_item->close();
    }
    
    $_SESSION['msg'] = '✅ Serviço cadastrado com sucesso!';
} else {
    $_SESSION['msg'] = '❌ Erro ao cadastrar serviço: ' . $stmt->error;
}

header("Location: servicos.php");
exit;