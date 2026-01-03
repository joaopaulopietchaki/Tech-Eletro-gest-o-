<?php
require "config.php";

echo "<h3>🔧 Corrigindo produtos com preço/custo zerado...</h3>";

$sql = "UPDATE produtos
        SET preco_venda = preco,
            preco_custo = custo
        WHERE preco_venda = 0 OR preco_custo = 0";
if ($conn->query($sql)) {
    echo "✅ Correção aplicada com sucesso!<br>";
} else {
    echo "❌ Erro: " . $conn->error;
}

echo "<br><a href='produtos.php'>Voltar</a>";