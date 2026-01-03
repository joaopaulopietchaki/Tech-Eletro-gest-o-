<?php
$host = "localhost";
$user = "joao0106_financeiro";
$pass = "padrao203040";
$db   = "joao0106_financeiro";

// Pasta para salvar
$dir = __DIR__ . "/backups";
if (!file_exists($dir)) mkdir($dir);

$file = $dir . "/backup_" . date("Y-m-d_H-i-s") . ".sql";
$cmd = "mysqldump --user={$user} --password={$pass} --host={$host} {$db} > {$file}";
system($cmd);

// Apagar backups mais antigos que 15 dias
$files = glob("$dir/*.sql");
foreach ($files as $f) {
    if (filemtime($f) < time() - 60*60*24*15) unlink($f);
}
?>
