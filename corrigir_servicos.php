<?php
require 'config.php';

echo "<pre>INICIANDO CORREÇÃO...\n";

/* =====================================
   1. CORRIGIR GASTOS EXTRAS ZERADOS
===================================== */
$q = $conn->query("
    SELECT id
    FROM servicos
    WHERE tipo = 'gasto_extra'
      AND (valor_recebido IS NULL OR valor_recebido = 0)
");

while ($r = $q->fetch_assoc()) {
    $conn->query("
        UPDATE servicos
        SET valor_recebido = 0,
            status_pagamento = 'Em aberto'
        WHERE id = {$r['id']}
    ");
    echo "✔ Gasto extra {$r['id']} ajustado\n";
}

/* =====================================
   2. CORRIGIR STATUS PAGO INDEVIDO
===================================== */
$conn->query("
    UPDATE servicos
    SET status_pagamento = 'Em aberto'
    WHERE valor_recebido = 0
");

echo "✔ Status corrigidos\n";

/* =====================================
   3. RECALCULAR SERVIÇOS
===================================== */
$q = $conn->query("
    SELECT *
    FROM servicos
    WHERE tipo = 'servico'
");

while ($s = $q->fetch_assoc()) {

    /* TOTAL ITENS */
    $it = $conn->query("
        SELECT SUM(subtotal) total
        FROM servico_itens
        WHERE servico_id = {$s['id']}
    ")->fetch_assoc();

    $totalItens = floatval($it['total'] ?? 0);

    /* DESLOCAMENTO */
    $desloc = floatval($s['desloc'] ?? 0);

    /* RESERVA */
    $reserva = floatval($s['reserva_emergencia'] ?? 0);

    /* LUCRO */
    $lucro = $s['valor_recebido'] - ($totalItens + $desloc + $reserva);
    if ($lucro < 0) $lucro = 0;

    $socio = $lucro / 2;

    $conn->query("
        UPDATE servicos
        SET
            custo_total = {$totalItens},
            lucro = {$lucro},
            socio1_valor = {$socio},
            socio2_valor = {$socio}
        WHERE id = {$s['id']}
    ");

    echo "✔ Serviço {$s['id']} recalculado | Lucro: R$ {$lucro}\n";
}

echo "\nFINALIZADO COM SUCESSO!\n</pre>";