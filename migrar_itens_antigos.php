<?php
require 'config.php';

echo "<h3>Migrando serviços antigos…</h3>";

$q = $conn->query("SELECT * FROM servicos");
$count = 0;

while($s = $q->fetch_assoc()){

    $id = $s['id'];

    // Se já tem itens, pula
    $chk = $conn->query("SELECT id FROM servico_itens WHERE servico_id=$id");
    if($chk->num_rows > 0) continue;

    $map = [
        'fornecedor1' => "Fornecedor 1",
        'fornecedor2' => "Fornecedor 2",
        'fornecedor3' => "Fornecedor 3",
        'fornecedor4' => "Fornecedor 4",
        'fornecedor5' => "Fornecedor 5",
    ];

    foreach($map as $campo=>$nome){
        $valor = (float)$s[$campo];
        if($valor > 0){
            $ins = $conn->prepare("
                INSERT INTO servico_itens (servico_id, produto_nome, quantidade, valor_unit, subtotal)
                VALUES (?,?,?,?,?)
            ");
            $qtd = 1;
            $valUnit = $valor;
            $subtotal = $valor;

            $ins->bind_param("isddd", $id, $nome, $qtd, $valUnit, $subtotal);
            $ins->execute();
        }
    }

    $count++;
}

echo "<p>✅ Migração concluída.<br>Serviços atualizados: $count</p>";
echo "<a href='servicos.php'>Voltar</a>";