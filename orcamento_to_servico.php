<?php
require "config.php";
if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$id = intval($_GET["id"] ?? 0);

// Buscar orçamento
$orc = $conn->prepare("
    SELECT o.*, c.nome AS cliente_nome
    FROM orcamentos o
    LEFT JOIN clientes c ON c.id = o.cliente_id
    WHERE o.id=?
");
$orc->bind_param("i", $id);
$orc->execute();
$dados = $orc->get_result()->fetch_assoc();

if(!$dados){
    $_SESSION['msg'] = "❌ Orçamento não encontrado!";
    header("Location: orcamentos.php");
    exit;
}

if($dados['status'] != "Aprovado"){
    $_SESSION['msg'] = "⚠️ Apenas orçamentos aprovados podem virar serviço!";
    header("Location: orcamento_view.php?id=".$id);
    exit;
}

// Inserir serviço
$ins = $conn->prepare("
    INSERT INTO servicos (nome_cliente, servico_executado, valor_recebido, data)
    VALUES (?, ?, 0, NOW())
");

$descricao_servico = "Serviço via orçamento #$id — ".$dados['descricao'];

$ins->bind_param("ss", $dados['cliente_nome'], $descricao_servico);
$ins->execute();
$serv_id = $conn->insert_id;

// Copiar itens do orçamento
$it = $conn->prepare("SELECT produto, unidade, quantidade, valor_unit FROM orcamento_itens WHERE orcamento_id=?");
$it->bind_param("i",$id);
$it->execute();
$res = $it->get_result();

if($res->num_rows > 0){
    $save = $conn->prepare("
        INSERT INTO servico_itens (servico_id, produto_nome, quantidade, valor_unit, subtotal)
        VALUES (?,?,?,?,?)
    ");

    while($i = $res->fetch_assoc()){
        $sub = $i['quantidade'] * $i['valor_unit'];
        $save->bind_param("isddd", $serv_id, $i['produto'], $i['quantidade'], $i['valor_unit'], $sub);
        $save->execute();
    }
}

// Atualiza status para convertido
$conn->query("UPDATE orcamentos SET status='Convertido em Serviço' WHERE id=".$id);

$_SESSION['msg'] = "✅ Orçamento convertido em serviço com sucesso!";
header("Location: servico_edit.php?id=".$serv_id);
exit;
?>