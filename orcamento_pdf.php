<?php
require "dompdf/autoload.inc.php";

use Dompdf\Dompdf;
use Dompdf\Options;

// ==============================
// Validação do ID
// ==============================
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("❌ ID do orçamento inválido.");
}

$id = intval($_GET['id']);

// ==============================
// Configuração da URL de origem
// ==============================
// URL do HTML usado para gerar o PDF
$url = "https://servicos.playtvtech.xyz/orcamento_print.php?id=" . $id;

// Obtém o HTML (com timeout e erro tratado)
$html = @file_get_contents($url);
if (!$html) {
    die("❌ Erro ao carregar o HTML de origem: {$url}");
}

// ==============================
// Configuração do DomPDF
// ==============================
$options = new Options();
$options->set('isRemoteEnabled', true); // permite imagens externas
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

// define diretório raiz (para imagens locais, se houver)
$options->setChroot(__DIR__);

// cria o objeto dompdf
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');

// Renderiza o PDF
$dompdf->render();

// ==============================
// Cabeçalhos HTTP e download
// ==============================
$pdfFileName = "orcamento_" . $id . ".pdf";

// Força download no navegador
$dompdf->stream($pdfFileName, [
    "Attachment" => true // true = baixar; false = abrir no navegador
]);
exit;
?>