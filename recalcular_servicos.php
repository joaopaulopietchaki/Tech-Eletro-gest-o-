<?php
require 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

/* ===== SEGURANÇA ===== */
if (!isset($_SESSION['user_id'])) die("Acesso negado");
$adm = $conn->query("SELECT role FROM usuarios WHERE id=".$_SESSION['user_id'])->fetch_assoc();
if (!$adm || $adm['role'] !== 'admin') die("Somente admin");

/* ===== CONFIGURAÇÕES ===== */
$CFG = loadSystemSettings();
$RESERVA_PERCENT = floatval($CFG['reserva'] ?? 10);

/* ===== BUSCA SERVIÇOS NÃO PAGOS ===== */
$sql = $conn->query("
    SELECT s.id, s.valor_recebido, s.desloc, s.status_pagamento
    FROM servicos s
    WHERE s.status_pagamento != 'Pago'
");

$recalculados = 0;

while ($s = $sql->fetch_assoc()) {

    $id = $s['id'];

    // soma itens
    $it = $conn->query("
        SELECT SUM(subtotal) AS total_itens
        FROM servico_itens
        WHERE servico_id = $id
    ")->fetch_assoc();

    $total_itens = floatval($it['total_itens'] ?? 0);
    $desloc = floatval($s['desloc'] ?? 0);

    $custo_total = round($total_itens + $desloc, 2);
    $lucro_bruto = round($s['valor_recebido'] - $custo_total, 2);

    $reserva = ($lucro_bruto > 0)
        ? round($lucro_bruto * ($RESERVA_PERCENT / 100), 2)
        : 0;

    $lucro_liq = round($lucro_bruto - $reserva, 2);

    $socio1 = round($lucro_liq / 2, 2);
    $socio2 = round($lucro_liq / 2, 2);

    $upd = $conn->prepare("
        UPDATE servicos SET
            custo_total = ?,
            lucro = ?,
            reserva = ?,
            socio1_valor = ?,
            socio2_valor = ?
        WHERE id = ?
    ");
    $upd->bind_param(
        "dddddi",
        $custo_total,
        $lucro_liq,
        $reserva,
        $socio1,
        $socio2,
        $id
    );
    $upd->execute();

    $recalculados++;
}

echo "<h3 style='text-align:center'>✅ $recalculados serviços recalculados com sucesso</h3>";