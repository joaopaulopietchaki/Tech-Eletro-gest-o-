<?php
require "config.php";
if (session_status()===PHP_SESSION_NONE) session_start();

$id = intval($_POST['id']);
$data = $_POST['data'];

if(!$id || !$data){
    $_SESSION['msg'] = "❌ Dados inválidos";
    header("Location: servicos.php");
    exit;
}

$stmt = $conn->prepare("UPDATE servicos SET garantia_fim=? WHERE id=?");
$stmt->bind_param("si",$data,$id);
$stmt->execute();

$_SESSION['msg'] = "✅ Garantia definida manualmente!";
header("Location: servico_view.php?id=".$id);
exit;