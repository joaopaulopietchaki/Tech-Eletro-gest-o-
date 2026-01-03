<?php
require 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$acao = $_POST['acao'] ?? '';
$ids  = $_POST['ids'] ?? [];

if (empty($ids)) {
    $_SESSION['msg'] = '⚠️ Nenhum item selecionado';
    header("Location: servicos.php");
    exit;
}

// Separa IDs por tipo
$ids_servicos = [];
$ids_gastos = [];

foreach ($ids as $item) {
    if (strpos($item, ':') !== false) {
        list($tipo, $id) = explode(':', $item, 2);
        $id = intval($id);
        
        if ($tipo === 'servico') {
            $ids_servicos[] = $id;
        } elseif ($tipo === 'gasto_extra') {
            $ids_gastos[] = $id;
        }
    }
}

switch ($acao) {

    case 'pago':
        // Atualiza serviços
        if (!empty($ids_servicos)) {
            $ids_str = implode(',', $ids_servicos);
            $conn->query("
                UPDATE servicos
                SET status_pagamento = 'Pago total',
                    data_quitacao = CURDATE()
                WHERE id IN ($ids_str)
            ");
        }

        // Atualiza gastos extras
        if (!empty($ids_gastos)) {
            $ids_str = implode(',', $ids_gastos);
            $conn->query("
                UPDATE gastos_extras
                SET pago = 1
                WHERE id IN ($ids_str)
            ");
        }
        
        $_SESSION['msg'] = '✅ Status atualizado para "Pago"';
        break;

    case 'aberto':
        // Atualiza serviços
        if (!empty($ids_servicos)) {
            $ids_str = implode(',', $ids_servicos);
            $conn->query("
                UPDATE servicos
                SET status_pagamento = 'Em aberto',
                    data_quitacao = NULL
                WHERE id IN ($ids_str)
            ");
        }

        // Atualiza gastos extras
        if (!empty($ids_gastos)) {
            $ids_str = implode(',', $ids_gastos);
            $conn->query("
                UPDATE gastos_extras
                SET pago = 0
                WHERE id IN ($ids_str)
            ");
        }
        
        $_SESSION['msg'] = '✅ Status atualizado para "Em aberto"';
        break;

    case 'excluir':
        // Exclui itens dos serviços
        if (!empty($ids_servicos)) {
            $ids_str = implode(',', $ids_servicos);
            
            $conn->query("
                DELETE FROM servico_itens
                WHERE servico_id IN ($ids_str)
            ");

            $conn->query("
                DELETE FROM servicos
                WHERE id IN ($ids_str)
            ");
        }

        // Exclui gastos extras
        if (!empty($ids_gastos)) {
            $ids_str = implode(',', $ids_gastos);
            
            $conn->query("
                DELETE FROM gastos_extras
                WHERE id IN ($ids_str)
            ");
        }
        
        $_SESSION['msg'] = '🗑️ Itens excluídos com sucesso!';
        break;

    default:
        $_SESSION['msg'] = '⚠️ Ação inválida';
}

header("Location: servicos.php");
exit;