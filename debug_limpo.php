<?php
// Nada de echo antes da sessão começar!
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config.php'; // carrega antes de qualquer saída

echo "<h3 style='color:green'>✅ Config.php carregado sem erros visíveis!</h3>";
require 'layout_header.php';
echo "<h3 style='color:green'>✅ Layout_header.php carregado!</h3>";
require 'layout_footer.php';
echo "<h3 style='color:green'>✅ Layout_footer.php carregado!</h3>";
