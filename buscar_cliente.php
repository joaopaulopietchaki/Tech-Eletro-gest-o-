<?php
require "config.php";
header("Content-Type: application/json");

$term = $_GET['term'] ?? '';

if (strlen($term) < 2) {
    echo json_encode([]);
    exit;
}

$like = "%$term%";

$stmt = $conn->prepare("
    SELECT id, nome, endereco, cidade, telefone
    FROM clientes
    WHERE nome LIKE ? OR telefone LIKE ? OR cidade LIKE ? OR endereco LIKE ?
    ORDER BY nome ASC
    LIMIT 20
");
$stmt->bind_param("ssss", $like, $like, $like, $like);
$stmt->execute();
$res = $stmt->get_result();

$clientes = [];

while ($row = $res->fetch_assoc()) {
    $clientes[] = [
        "id"       => $row["id"],
        "label"    => $row["nome"] . " — " . $row["cidade"] . " (" . $row["telefone"] . ")",
        "value"    => $row["nome"],
        "endereco" => $row["endereco"],
        "cidade"   => $row["cidade"],
        "telefone" => $row["telefone"]
    ];
}

echo json_encode($clientes);
$stmt->close();
?>