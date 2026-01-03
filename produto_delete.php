<?php
require "config.php";

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['msg'] = "Produto inválido.";
    header("Location: produtos.php");
    exit;
}

// Busca imagem para deletar
$res = $conn->query("SELECT imagem FROM produtos WHERE id = $id");
if ($res && $res->num_rows > 0) {
    $p = $res->fetch_assoc();
    if (!empty($p['imagem']) && file_exists(__DIR__ . "/uploads/produtos/" . $p['imagem'])) {
        unlink(__DIR__ . "/uploads/produtos/" . $p['imagem']);
    }

    $conn->query("DELETE FROM produtos WHERE id = $id");
    $_SESSION['msg'] = "🗑️ Produto excluído com sucesso!";
} else {
    $_SESSION['msg'] = "⚠️ Produto não encontrado.";
}

header("Location: produtos.php");
exit;
?>