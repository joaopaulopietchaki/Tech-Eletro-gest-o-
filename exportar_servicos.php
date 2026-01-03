<?php
require 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) exit;

$ids = $_POST['ids'] ?? [];
if (empty($ids)) exit;

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=servicos_exportados.csv');

$output = fopen('php://output', 'w');

// Cabeçalho CSV
fputcsv($output, [
    'Data','Cliente','Serviço','Valor Recebido',
    'Custos','Reserva','Lucro',
    'Sócio 1','Sócio 2','Status'
], ';');

foreach ($ids as $id) {
    $id = (int)$id;
    $s = $conn->query("SELECT * FROM servicos WHERE id=$id")->fetch_assoc();
    if (!$s) continue;

    fputcsv($output, [
        date('d/m/Y', strtotime($s['data'])),
        $s['nome_cliente'],
        $s['servico_executado'],
        number_format($s['valor_recebido'],2,',','.'),
        number_format($s['custo_total'],2,',','.'),
        number_format($s['reserva_emergencia'],2,',','.'),
        number_format($s['lucro'],2,',','.'),
        number_format($s['socio1_valor'],2,',','.'),
        number_format($s['socio2_valor'],2,',','.'),
        $s['status_pagamento']
    ], ';');
}

fclose($output);
exit;