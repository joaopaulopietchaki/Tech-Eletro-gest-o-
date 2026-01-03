<?php
require "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = (int)($_POST['orcamento_id'] ?? 0);
if ($id <= 0) {
    $_SESSION['msg'] = "⚠️ Orçamento inválido.";
    header("Location: orcamentos.php");
    exit;
}

/* === Buscar orçamento e cliente === */
$orc_q = $conn->prepare("SELECT * FROM orcamentos WHERE id = ?");
$orc_q->bind_param("i", $id);
$orc_q->execute();
$orc = $orc_q->get_result()->fetch_assoc();

if (!$orc) {
    $_SESSION['msg'] = "⚠️ Orçamento não encontrado.";
    header("Location: orcamentos.php");
    exit;
}

$cliente_q = $conn->prepare("SELECT id, nome, telefone, cidade FROM clientes WHERE id = ?");
$cliente_q->bind_param("i", $orc['cliente_id']);
$cliente_q->execute();
$cliente = $cliente_q->get_result()->fetch_assoc();

/* === Itens === */
$itens_q = $conn->prepare("SELECT produto, unidade, quantidade, valor_unit FROM orcamento_itens WHERE orcamento_id = ?");
$itens_q->bind_param("i", $id);
$itens_q->execute();
$itens = $itens_q->get_result()->fetch_all(MYSQLI_ASSOC);

/* === Cálculo total === */
$total_itens = 0;
foreach ($itens as $i) {
    $total_itens += ((float)$i['quantidade'] * (float)$i['valor_unit']);
}

/* === Criar nova OS === */
$stmt = $conn->prepare("
    INSERT INTO ordens_servico 
    (cliente_id, tipo_servico, descricao, status, data_agendada, valor_total, custo_total, tecnico, observacoes) 
    VALUES (?, ?, ?, 'Pendente', NOW(), ?, 0, '', '')
");

$tipo_serv = "Serviço referente ao Orçamento #" . $id;
$stmt->bind_param("issd", $orc['cliente_id'], $tipo_serv, $orc['descricao'], $total_itens);
$stmt->execute();
$id_os = $stmt->insert_id;

/* === Copiar itens para tabela de itens da OS (se existir) === */
if ($conn->query("SHOW TABLES LIKE 'os_itens'")->num_rows > 0) {
    $it_stmt = $conn->prepare("INSERT INTO os_itens (os_id, produto, unidade, quantidade, valor_unit, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($itens as $i) {
        $q = (float)$i['quantidade'];
        $v = (float)$i['valor_unit'];
        $s = $q * $v;
        $it_stmt->bind_param("issddd", $id_os, $i['produto'], $i['unidade'], $q, $v, $s);
        $it_stmt->execute();
    }
}

/* === Atualizar status do orçamento === */
$conn->query("UPDATE orcamentos SET status='Aprovado' WHERE id = $id");

/* === Mensagem e redirecionamento === */
$_SESSION['msg'] = "✅ Orçamento convertido com sucesso em OS #$id_os!";
header("Location: os_edit.php?id=" . $id_os);
exit;
?>
