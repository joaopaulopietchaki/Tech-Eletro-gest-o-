<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo "<h3>Testando include config.php...</h3>";
    require 'config.php';
    echo "<p>Config.php carregado com sucesso!</p>";

    echo "<h3>Testando include layout_header.php...</h3>";
    require 'layout_header.php';
    echo "<p>Layout_header carregado com sucesso!</p>";

    echo "<h3>Testando include layout_footer.php...</h3>";
    require 'layout_footer.php';
    echo "<p>Layout_footer carregado com sucesso!</p>";

    echo "<h3 style='color:green'>✅ Tudo carregou sem erro PHP!</h3>";
} catch (Throwable $e) {
    echo "<h3 style='color:red'>❌ ERRO:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<p>Arquivo: " . $e->getFile() . " Linha: " . $e->getLine() . "</p>";
}
