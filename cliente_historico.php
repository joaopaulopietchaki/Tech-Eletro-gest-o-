<?php
require "config.php";
header("Content-Type: application/json");

$acao = $_POST["acao"] ?? "";

if ($acao == "add") {
    $id = intval($_POST["cliente_id"]);
    $desc = trim($_POST["descricao"]);

    $stmt = $conn->prepare("
        INSERT INTO cliente_historico (cliente_id, tipo, descricao, data)
        VALUES (?, 'Anotação', ?, NOW())
    ");
    $stmt->bind_param("is", $id, $desc);
    $stmt->execute();

    echo json_encode([
        "status" => "ok",
        "id"     => $stmt->insert_id,
        "data"   => date("d/m/Y H:i")
    ]);
    exit;
}

if ($acao == "delete") {
    $id = intval($_POST["id"]);
    $conn->query("DELETE FROM cliente_historico WHERE id = $id");

    echo json_encode(["status" => "ok"]);
    exit;
}

echo json_encode(["status" => "erro"]);
?>