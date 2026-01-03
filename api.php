<?php
require_once "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

header("Content-Type: application/json; charset=utf-8");

// =======================
// API UNIFICADA TECH ELETRO
// =======================
// Ações suportadas:
// ?acao=buscar_cliente&q=termo
// ?acao=listar_os
// POST ?acao=criar_os
// ?acao=detalhes_os&id=...

$acao = $_GET['acao'] ?? ($_POST['acao'] ?? '');

if (!$acao) {
  echo json_encode(["erro" => "Nenhuma ação informada."]);
  exit;
}

switch ($acao) {

  // 🔍 BUSCAR CLIENTE
  case 'buscar_cliente':
    $q = trim($_GET['q'] ?? '');
    if ($q === '') { echo json_encode([]); exit; }
    $stmt = $conn->prepare("
      SELECT id, nome, telefone, endereco 
      FROM clientes 
      WHERE nome LIKE ? OR telefone LIKE ? 
      ORDER BY nome ASC LIMIT 10
    ");
    $like = "%$q%";
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    $out = [];
    while ($r = $res->fetch_assoc()) $out[] = $r;
    echo json_encode($out);
    break;

  // 📅 LISTAR OS PARA O CALENDÁRIO
  case 'listar_os':
    $tec = trim($_GET['tecnico'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $where = [];
    if ($tec !== '') $where[] = "tecnico = '".$conn->real_escape_string($tec)."'";
    if ($status !== '') $where[] = "status = '".$conn->real_escape_string($status)."'";
    $sql = "SELECT * FROM agenda_os".($where ? " WHERE ".implode(" AND ", $where) : "");
    $res = $conn->query($sql);
    $ev = [];
    while ($r = $res->fetch_assoc()) {
      $cor = '#6c757d';
      if (stripos($r['status'], 'andamento') !== false) $cor = '#0dcaf0';
      if (stripos($r['status'], 'conclu') !== false) $cor = '#198754';
      if (stripos($r['status'], 'cancel') !== false) $cor = '#dc3545';
      if (stripos($r['status'], 'abert') !== false) $cor = '#ffc107';
      $ev[] = [
        'id' => $r['id'],
        'title' => $r['cliente'].' - '.$r['servico'],
        'start' => $r['data'].($r['hora'] ? "T{$r['hora']}" : ""),
        'backgroundColor' => $cor,
        'borderColor' => $cor,
        'textColor' => '#fff',
        'extendedProps' => $r
      ];
    }
    echo json_encode($ev);
    break;

  // 🧾 CRIAR NOVA OS
  case 'criar_os':
    $campos = [
      'cliente','contato','endereco','tipo_servico','servico','equipamento',
      'problema','data','hora','tecnico','status','valor','pagamento_status','observacoes'
    ];
    $sql = "INSERT INTO agenda_os (".implode(",", $campos).") VALUES (".implode(",", array_fill(0, count($campos), "?")).")";
    $stmt = $conn->prepare($sql);
    $tipos = str_repeat("s", count($campos));
    $vals = array_map(fn($c) => $_POST[$c] ?? null, $campos);
    $stmt->bind_param($tipos, ...$vals);
    $ok = $stmt->execute();
    echo json_encode(["ok" => $ok, "id" => $conn->insert_id]);
    break;

  // 🔎 DETALHES DE UMA OS
  case 'detalhes_os':
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $conn->prepare("SELECT * FROM agenda_os WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $os = $stmt->get_result()->fetch_assoc();
    if (!$os) {
      echo json_encode(["erro" => "OS não encontrada"]);
    } else {
      echo json_encode($os);
    }
    break;

  default:
    echo json_encode(["erro" => "Ação inválida: ".$acao]);
}