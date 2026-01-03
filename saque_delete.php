<?php
require 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$id = intval($_GET['id']);
$conn->query("DELETE FROM saques WHERE id=$id");

$_SESSION['msg']="Saque removido ✅";
header("Location: socio.php");
exit;
