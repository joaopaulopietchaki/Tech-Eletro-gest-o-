<?php
// os_events.php -> retorna eventos JSON para FullCalendar
require_once "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

$events = [];
try {
    // Seleciona id, cliente, servico, data_agendada, hora_agendada, status
    $sql = "SELECT id, nome_cliente, servico_executado, data_agendada, hora_agendada, status FROM servicos WHERE data_agendada IS NOT NULL LIMIT 1000";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $date = $r['data_agendada'];
            $time = $r['hora_agendada'] ?? null;
            $start = $date;
            if (!empty($time)) {
                // concat hora se temperada
                $start = $date . 'T' . substr($time,0,5);
            }
            $title = trim(($r['nome_cliente'] ?? '') . ' — ' . ($r['servico_executado'] ?? 'OS'));
            $events[] = [
                'id' => $r['id'],
                'title' => $title,
                'start' => $start,
                'allDay' => empty($time),
                'os_id' => (int)$r['id'],
                'status' => $r['status'] ?? ''
            ];
        }
    }
} catch (\Throwable $e) {
    // retorno vazio em erro
}

echo json_encode($events, JSON_UNESCAPED_UNICODE);