<?php
error_reporting(0);
ini_set('display_errors', 0);

require "config.php"; 

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) die("ID inválido");
$id = intval($_GET['id']);

// === CONSULTA PRINCIPAL ===
$orc = $conn->query("
    SELECT o.*, c.nome AS cliente_nome 
    FROM orcamentos o 
    LEFT JOIN clientes c ON c.id=o.cliente_id 
    WHERE o.id=$id
")->fetch_assoc();

if (!$orc) die("Orçamento não encontrado.");

$cliente = $orc['cliente_nome'] ?? "Cliente não informado";
$data = $orc['data'] ?? date("Y-m-d");

// === ITENS ===
$itens = $conn->query("SELECT * FROM orcamento_itens WHERE orcamento_id = $id");

// === EMPRESA ===
$empresa = $conn->query("SELECT * FROM settings WHERE id=1")->fetch_assoc() ?? [];
$empresa_nome  = $empresa['empresa']   ?? "PlayTV Tech";
$empresa_cnpj  = $empresa['cnpj']      ?? "00.000.000/0000-00";
$empresa_tel   = $empresa['telefone']  ?? "(42) 99821-4028";
$empresa_email = $empresa['email']     ?? "contato@playtvtech.xyz";
$empresa_logo  = $empresa['logo_file'] ?? "logo.png";

// === CAMINHOS LOCAIS (para DomPDF) ===
$basePath = __DIR__ . "/uploads/";
$prodPath = __DIR__ . "/uploads/produtos/";

// Verifica logo da empresa
if (!empty($empresa_logo) && file_exists($basePath . basename($empresa_logo))) {
    $empresa_logo = $basePath . basename($empresa_logo);
} else {
    $empresa_logo = $basePath . "logo.png";
}

// === QR CODE WhatsApp ===
$whatsapp_url = "https://wa.me/5542998214028?text=Olá,+quero+informações+sobre+o+orçamento+" . $id;
$qr_url = "https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=" . urlencode($whatsapp_url);

// === Selo de autenticidade ===
$hash = strtoupper(substr(sha1($empresa_nome . $empresa_cnpj . $id . date('Ymd')), 0, 12));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Orçamento #<?= $id ?> — <?= htmlspecialchars($empresa_nome) ?></title>

<style>
@page {
    margin: 80px 40px;
}

/* Marca d’água */
body::before {
    content: "";
    position: fixed;
    top: 35%;
    left: 20%;
    width: 60%;
    height: 60%;
    background: url('<?= $empresa_logo ?>') no-repeat center center;
    background-size: 40%;
    opacity: 0.07;
    z-index: -1;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 13px;
    color: #333;
    margin: 0;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 3px solid #0A66C2;
    padding-bottom: 10px;
    margin-bottom: 20px;
}
.logo img { max-height: 70px; }
.qrcode img { width: 110px; }

h3 { color: #0A66C2; margin-top: 0; }

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}
th {
    background: #0A66C2;
    color: #fff;
    padding: 8px;
    font-size: 13px;
}
td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: center;
}
tr:nth-child(even){ background: #F8F9FA; }

img.produto {
    width: 60px;
    height: auto;
    border-radius: 6px;
}

.button {
    background: #25D366;
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    display: inline-block;
    margin-top: 15px;
}

.assinatura {
    text-align: center;
    margin-top: 40px;
}
.assinatura img {
    width: 140px;
    opacity: 0.9;
}

.selo {
    text-align: center;
    margin-top: 15px;
    font-size: 11px;
    color: #555;
}
.selo span {
    background: #e6f0ff;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: bold;
    color: #0A66C2;
}

.footer {
    position: fixed;
    bottom: 10px;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 11px;
    color: #666;
    border-top: 1px solid #ccc;
    padding-top: 5px;
}

/* Numeração de páginas */
@page {
    @bottom-right {
        content: "Página " counter(page) " de " counter(pages);
        font-size: 11px;
        color: #444;
    }
}
</style>
</head>

<body>

<div class="header">
    <div class="logo"><img src="<?= $empresa_logo ?>" alt="Logo"></div>
    <div class="qrcode"><img src="<?= $qr_url ?>" alt="QR Code"></div>
</div>

<h3>Orçamento #<?= $id ?></h3>
<p><b>Cliente:</b> <?= htmlspecialchars($cliente) ?></p>
<p><b>Data:</b> <?= date("d/m/Y", strtotime($data)) ?></p>

<table>
<tr>
    <th>Imagem</th>
    <th>Descrição</th>
    <th>Qtd</th>
    <th>Valor</th>
    <th>Total</th>
</tr>

<?php 
$total = 0;
while ($i = $itens->fetch_assoc()):
    $descricao = $i['produto'];
    $qtd       = floatval($i['quantidade']);
    $valor     = floatval($i['valor_unit']);
    $linha     = $valor * $qtd;

    // Imagem local do produto
    if (!empty($i['imagem']) && file_exists($prodPath . basename($i['imagem']))) {
        $img = $prodPath . basename($i['imagem']);
    } else {
        $img = "https://via.placeholder.com/60";
    }

    $total += $linha;
?>
<tr>
    <td><img src="<?= $img ?>" class="produto" alt="Produto"></td>
    <td><?= htmlspecialchars($descricao) ?></td>
    <td><?= $qtd ?></td>
    <td>R$ <?= number_format($valor, 2, ',', '.') ?></td>
    <td>R$ <?= number_format($linha, 2, ',', '.') ?></td>
</tr>
<?php endwhile; ?>

<tr>
    <td colspan="4" style="text-align:right"><b>Total:</b></td>
    <td><b>R$ <?= number_format($total, 2, ',', '.') ?></b></td>
</tr>
</table>

<div style="text-align:center;">
    <a class="button" href="<?= $whatsapp_url ?>" target="_blank">💬 Falar no WhatsApp</a>
</div>

<div class="assinatura">
    <img src="<?= $empresa_logo ?>" alt="Assinatura">
    <p><small>Assinado digitalmente por <b><?= htmlspecialchars($empresa_nome) ?></b><br>
    <?= $empresa_cnpj ?></small></p>
</div>

<div class="selo">
    Selo de Autenticidade: <span><?= $hash ?></span><br>
    Documento gerado automaticamente em <?= date('d/m/Y H:i') ?>
</div>

<div class="footer">
    <?= htmlspecialchars($empresa_nome) ?> — <?= $empresa_cnpj ?><br>
    <?= $empresa_tel ?> — <?= $empresa_email ?><br>
    <a href="https://servicos.playtvtech.xyz" target="_blank">https://servicos.playtvtech.xyz</a>
</div>

</body>
</html>