<?php
require 'config.php';
if (session_status()===PHP_SESSION_NONE) session_start();

// Apenas admin
$adm = $conn->query("SELECT role FROM usuarios WHERE id=".$_SESSION['user_id'])->fetch_assoc();
if($adm['role']!='admin'){ die("❌ Acesso negado"); }

$id = intval($_GET['id']);

// Impede exclusão do admin principal
if($id == 1){
    die("<h3 style='color:red;text-align:center;'>❌ Você não pode excluir o administrador principal!</h3>");
}

$conn->query("DELETE FROM usuarios WHERE id=$id");
header("Location: usuarios.php");
exit;