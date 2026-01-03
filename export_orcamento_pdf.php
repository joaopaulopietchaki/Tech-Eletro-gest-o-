<?php
require "dompdf/autoload.inc.php";
use Dompdf\Dompdf;

if (!isset($_GET['id'])) die("ID inválido");
$id = intval($_GET['id']);

$html = file_get_contents("https://".$_SERVER['HTTP_HOST']."/orcamento_print.php?id=".$id);

$dompdf = new Dompdf(['isRemoteEnabled'=>true]);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("orcamento_$id.pdf", ["Attachment"=>true]);