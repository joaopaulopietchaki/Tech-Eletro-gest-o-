<?php
require "config.php";
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

$term = trim($_GET['term'] ?? '');

if (strlen($term) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, nome, telefone, cpf_cnpj, cidade, endereco, email
    FROM clientes
    WHERE nome LIKE CONCAT('%', ?, '%')
       OR telefone LIKE CONCAT('%', ?, '%')
       OR cpf_cnpj LIKE CONCAT('%', ?, '%')
    ORDER BY nome ASC
    LIMIT 15
");

$stmt->bind_param("sss", $term, $term, $term);
$stmt->execute();
$result = $stmt->get_result();

$clientes = [];
while ($row = $result->fetch_assoc()) {
    $clientes[] = [
        'id' => $row['id'],
        'nome' => $row['nome'],
        'telefone' => $row['telefone'] ?? '',
        'cidade' => $row['cidade'] ?? '',
        'endereco' => $row['endereco'] ?? '',
        'cpf_cnpj' => $row['cpf_cnpj'] ?? '',
        'email' => $row['email'] ?? '',
        'value' => $row['nome'], // para autocomplete
        'label' => $row['nome'] . ' - ' . ($row['telefone'] ?? '')
    ];
}

echo json_encode($clientes, JSON_UNESCAPED_UNICODE);