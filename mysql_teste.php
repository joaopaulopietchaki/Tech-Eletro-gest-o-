<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$db   = "joao0106_financeiro";
$user = "joao0106_financeiro";
$pass = "padrao203040";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("<h3 style='color:red'>❌ Erro MySQL: " . $conn->connect_error . "</h3>");
}

echo "<h3 style='color:green'>✅ Conexão MySQL OK!</h3>";
?>
