<?php
require 'config.php';
if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$ini = $_GET['ini'] ?? '';
$fim = $_GET['fim'] ?? '';
$tipo = $_GET['tipo'] ?? 'servicos';

if ($tipo === 'servicos') {
    $sql = "SELECT data,nome_cliente,servico_executado,valor_recebido,custo_total,reserva_emergencia,socio1_valor,socio2_valor,lucro 
            FROM servicos WHERE data BETWEEN ? AND ? ORDER BY data ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $ini, $fim);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $sql = "SELECT nome_cliente, COUNT(*) as total_servicos, SUM(valor_recebido) as total_valor 
            FROM servicos WHERE data BETWEEN ? AND ? GROUP BY nome_cliente ORDER BY total_valor DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $ini, $fim);
    $stmt->execute();
    $res = $stmt->get_result();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=relatorio_'.$tipo.'_'.date('Y-m-d').'.csv');

$out = fopen('php://output', 'w');

if ($tipo === 'servicos') {
    fputcsv($out, ['Data','Cliente','Serviço','Recebido','Custo','Reserva','Sócio 1','Sócio 2','Lucro'], ';');
    
    if ($res) while($r = $res->fetch_assoc()) {
        fputcsv($out, [
            date('d/m/Y', strtotime($r['data'])),
            $r['nome_cliente'],
            $r['servico_executado'],
            number_format($r['valor_recebido'],2,',','.'),
            number_format($r['custo_total'],2,',','.'),
            number_format($r['reserva_emergencia'],2,',','.'),
            number_format($r['socio1_valor'],2,',','.'),
            number_format($r['socio2_valor'],2,',','.'),
            number_format($r['lucro'],2,',','.')
        ], ';');
    }
} else {
    fputcsv($out, ['Cliente','Total Serviços','Valor Total'], ';');
    
    if ($res) while($r = $res->fetch_assoc()) {
        fputcsv($out, [
            $r['nome_cliente'],
            $r['total_servicos'],
            number_format($r['total_valor'],2,',','.')
        ], ';');
    }
}

fclose($out);