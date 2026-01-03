<?php
require "config.php";
header("Content-Type: application/json; charset=UTF-8");

$q = $_GET["q"] ?? "";
$q = trim($q);

function number_or_zero($v){
    return ($v === null || $v === "" ? 0 : $v);
}

if ($q === "") {
    $sql = "
        SELECT id, nome,
               COALESCE(preco_venda, 0) AS preco_venda,
               COALESCE(preco_custo, 0) AS preco_custo,
               descricao,
               imagem,
               COALESCE(tipo,'produto') AS tipo
        FROM produtos
        ORDER BY nome ASC
        LIMIT 200
    ";
    $result = $conn->query($sql);
} else {
    $search = "%$q%";
    $stmt = $conn->prepare("
        SELECT id, nome,
               COALESCE(preco_venda, 0) AS preco_venda,
               COALESCE(preco_custo, 0) AS preco_custo,
               descricao,
               imagem,
               COALESCE(tipo,'produto') AS tipo
        FROM produtos
        WHERE nome LIKE ? OR descricao LIKE ?
        ORDER BY nome ASC
        LIMIT 200
    ");
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
}

$produtos = [];
while ($p = $result->fetch_assoc()) {
    $produtos[] = [
        "id" => $p["id"],
        "nome" => $p["nome"],
        "preco_venda" => number_or_zero($p["preco_venda"]),
        "preco_custo" => number_or_zero($p["preco_custo"]),
        "descricao" => $p["descricao"] ?: "",
        "imagem" => $p["imagem"] ?: "",
        "tipo" => $p["tipo"] ?: "produto"
    ];
}

echo json_encode($produtos);
exit;