<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'config.php';

echo "OK at«± aqui<br>";

$stmt = $conn->prepare("SELECT * FROM os LIMIT 1");
$stmt->execute();

echo "Consulta OK<br>";