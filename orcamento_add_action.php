<?php
require "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

function br_to_float($v){
    if ($v === null || $v === "") return 0;
    $v = str_replace(['.', ' ', 'R$'], '', $v);
    $v = str_replace(',', '.', $v);
    return floatval($v);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: orcamento_add.php");
    exit;
}

// DADOS DO CLIENTE
$cliente_id   = intval($_POST['cliente_id'] ?? 0);
$cliente_nome = trim($_POST['cliente_nome'] ?? '');
$telefone     = trim($_POST['telefone'] ?? '');
$cidade       = trim($_POST['cidade'] ?? '');
$endereco     = trim($_POST['endereco'] ?? '');

// DADOS DO ORÇAMENTO
$descricao    = trim($_POST['descricao'] ?? '');
$status       = $_POST['status'] ?? 'Pendente';
$garantia     = $_POST['garantia'] ?? 'Sem Garantia';

// VALORES
$valor_total       = floatval($_POST['valor_total'] ?? 0);
$valor_custo_total = floatval($_POST['valor_custo_total'] ?? 0);
$valor_desc_total  = floatval($_POST['valor_desconto_total'] ?? 0);

// VALIDAÇÕES
if (empty($cliente_nome)) {
    $_SESSION['error'] = "Nome do cliente é obrigatório!";
    header("Location: orcamento_add.php");
    exit;
}

// SALVAR ORÇAMENTO
$stmt = $conn->prepare("
    INSERT INTO orcamentos 
    (cliente_id, cliente_nome, telefone, cidade, endereco, descricao, 
     garantia_tipo, status, valor_total, valor_custo_total, valor_desconto_total, 
     data_criacao)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

$stmt->bind_param(
    "isssssssddd",
    $cliente_id, $cliente_nome, $telefone, $cidade, $endereco,
    $descricao, $garantia, $status, $valor_total, $valor_custo_total, $valor_desc_total
);

if (!$stmt->execute()) {
    $_SESSION['error'] = "Erro ao criar orçamento: " . $stmt->error;
    header("Location: orcamento_add.php");
    exit;
}

$orcamento_id = $stmt->insert_id;

// SALVAR ITENS
$produto_id   = $_POST['produto_id'] ?? [];
$item_nome    = $_POST['item_nome'] ?? [];
$item_unidade = $_POST['unidade'] ?? [];
$item_qtd     = $_POST['item_qtd'] ?? [];
$item_valor   = $_POST['item_valor'] ?? [];
$item_total   = $_POST['item_total'] ?? [];

$stmt_item = $conn->prepare("
    INSERT INTO orcamentos_itens
    (orcamento_id, produto_id, nome, unidade, qtd, preco_unit, subtotal)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

for ($i = 0; $i < count($item_nome); $i++) {
    if (trim($item_nome[$i]) === "") continue;
    
    $prod_id = intval($produto_id[$i] ?? 0);
    $nome = trim($item_nome[$i]);
    $unid = trim($item_unidade[$i] ?? 'un');
    $qtd = br_to_float($item_qtd[$i] ?? 0);
    $preco = br_to_float($item_valor[$i] ?? 0);
    $sub = br_to_float($item_total[$i] ?? 0);
    
    if ($qtd <= 0) $qtd = 1;
    if ($sub == 0) $sub = $qtd * $preco;
    
    $stmt_item->bind_param(
        "iissddd",
        $orcamento_id, $prod_id, $nome, $unid, $qtd, $preco, $sub
    );
    $stmt_item->execute();
}

$_SESSION['message'] = "✅ Orçamento #$orcamento_id criado com sucesso!";
header("Location: orcamento_view.php?id=$orcamento_id");
exit;