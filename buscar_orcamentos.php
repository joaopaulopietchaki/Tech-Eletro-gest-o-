<?php
require "config.php"; 
header('Content-Type: application/json; charset=utf-8');

$term = trim($_GET['term'] ?? '');

if (strlen($term) < 1) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        o.id, 
        o.cliente_nome,
        o.data_criacao,
        o.status,
        o.valor_total
    FROM orcamentos o
    WHERE o.id LIKE CONCAT('%', ?, '%')
       OR o.cliente_nome LIKE CONCAT('%', ?, '%')
       OR o.descricao LIKE CONCAT('%', ?, '%')
    ORDER BY o.id DESC
    LIMIT 10
");

$stmt->bind_param("sss", $term, $term, $term);
$stmt->execute();
$result = $stmt->get_result();

$orcamentos = [];
while ($row = $result->fetch_assoc()) {
    $orcamentos[] = [
        'id' => $row['id'],
        'label' => 'Nº ' . $row['id'] . ' - ' . $row['cliente_nome'], 
        'value' => $row['id'], 
        'cliente' => $row['cliente_nome'],
        'status' => $row['status'],
        'valor' => $row['valor_total'],
        'data' => date('d/m/Y', strtotime($row['data_criacao']))
    ];
}

echo json_encode($orcamentos, JSON_UNESCAPED_UNICODE);