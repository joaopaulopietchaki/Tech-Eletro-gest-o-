<?php
require 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

function br_to_float($v){
    $v = str_replace(['.', ' '], '', $v);
    $v = str_replace(',', '.', $v);
    return floatval($v);
}

$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['msg'] = '⚠️ ID inválido';
    header("Location: servicos.php");
    exit;
}

$descricao = trim($_POST['descricao'] ?? '');
$valor     = br_to_float($_POST['valor'] ?? '0,00');
$data      = $_POST['data'] ?? date('Y-m-d');
$pago      = intval($_POST['pago'] ?? 0);

if ($descricao === '' || $valor <= 0) {
    $_SESSION['msg'] = '⚠️ Descrição e valor são obrigatórios';
    header("Location: gasto_edit.php?id=$id");
    exit;
}

$stmt = $conn->prepare("
    UPDATE gastos_extras SET
        descricao = ?,
        valor = ?,
        data = ?,
        pago = ?
    WHERE id = ?
");

$stmt->bind_param('sdsii', $descricao, $valor, $data, $pago, $id);

if ($stmt->execute()) {
    $_SESSION['msg'] = '✅ Gasto atualizado com sucesso!';
} else {
    $_SESSION['msg'] = '❌ Erro ao atualizar gasto: ' . $stmt->error;
}

header("Location: servicos.php");
exit;