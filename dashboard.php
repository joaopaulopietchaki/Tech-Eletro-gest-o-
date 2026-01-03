<?php
require "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

require "layout_header.php";

/* ===============================
   CONTADORES OFICIAIS
=============================== */
$hoje = date("Y-m-d");
$semana_ini = date("Y-m-d", strtotime("monday this week"));
$semana_fim = date("Y-m-d", strtotime("sunday this week"));

$os_dia = $conn->query("
    SELECT COUNT(*) AS t FROM os
    WHERE data_agendada = '$hoje'
      AND status NOT IN ('Concluída', 'Cancelada')
")->fetch_assoc()['t'];

$os_semana = $conn->query("
    SELECT COUNT(*) AS t FROM os
    WHERE data_agendada BETWEEN '$semana_ini' AND '$semana_fim'
      AND status NOT IN ('Concluída', 'Cancelada')
")->fetch_assoc()['t'];

$os_atrasadas = $conn->query("
    SELECT COUNT(*) AS t FROM os
    WHERE data_agendada < '$hoje'
      AND status NOT IN ('Concluída', 'Cancelada')
")->fetch_assoc()['t'];

$os_total = $conn->query("SELECT COUNT(*) AS t FROM os")->fetch_assoc()['t'];

/* ===============================
   PRÓXIMAS OS
=============================== */
$prox = $conn->query("
    SELECT id, cliente_nome, servico, data_agendada, hora_agendada, status
    FROM os
    WHERE data_agendada >= '$hoje'
      AND status NOT IN ('Concluída','Cancelada')
    ORDER BY data_agendada ASC, hora_agendada ASC
    LIMIT 10
");
?>

<div class="container-fluid">

    <h3 class="fw-bold mb-4"><i class="bi bi-speedometer2"></i> Dashboard</h3>

    <!-- ROW CARDS -->
    <div class="row g-3">

        <div class="col-6 col-lg-3">
            <div class="p-3 shadow-sm rounded bg-white border-start border-primary border-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-calendar-day fs-3 text-primary me-3"></i>
                    <div>
                        <small class="text-muted">OS do Dia</small>
                        <h4 class="m-0"><?= $os_dia ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="p-3 shadow-sm rounded bg-white border-start border-info border-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-calendar-week fs-3 text-info me-3"></i>
                    <div>
                        <small class="text-muted">OS da Semana</small>
                        <h4 class="m-0"><?= $os_semana ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="p-3 shadow-sm rounded bg-white border-start border-danger border-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle fs-3 text-danger me-3"></i>
                    <div>
                        <small class="text-muted">Atrasadas</small>
                        <h4 class="text-danger m-0"><?= $os_atrasadas ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="p-3 shadow-sm rounded bg-white border-start border-success border-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-clipboard-check fs-3 text-success me-3"></i>
                    <div>
                        <small class="text-muted">Total de OS</small>
                        <h4 class="m-0"><?= $os_total ?></h4>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- BUSCA -->
    <div class="card shadow-sm p-3 mt-4">
        <h5 class="mb-2"><i class="bi bi-search"></i> Buscar OS</h5>
        <input type="text" id="buscaOs" class="form-control" placeholder="Cliente, serviço, ID ou status...">
    </div>

    <!-- PRÓXIMAS OS -->
    <div class="card shadow-sm p-3 mt-4">
        <h5 class="mb-3"><i class="bi bi-list-task"></i> Próximas OS</h5>

        <div id="listaCardsOS">

            <?php if ($prox->num_rows > 0): ?>
                <?php while ($os = $prox->fetch_assoc()): ?>

                    <div class="p-3 shadow-sm rounded bg-white mb-3 os-card">

                        <div class="d-flex justify-content-between">
                            <strong>#<?= $os['id'] ?> — <?= htmlspecialchars($os['cliente_nome']) ?></strong>

                            <?php
                                $statusClass = match($os['status']) {
                                    'Em aberto' => 'warning',
                                    'Em andamento' => 'primary',
                                    'Aguardando peça' => 'info',
                                    default => 'secondary'
                                };
                            ?>
                            <span class="badge bg-<?= $statusClass ?>">
                                <?= $os['status'] ?>
                            </span>
                        </div>

                        <div class="text-muted">
                            <?= htmlspecialchars($os['servico']) ?>
                        </div>

                        <div class="d-flex justify-content-between mt-2">
                            <span><i class="bi bi-calendar"></i> <?= date('d/m/Y', strtotime($os['data_agendada'])) ?></span>
                            <span><i class="bi bi-clock"></i> <?= $os['hora_agendada'] ? substr($os['hora_agendada'],0,5) : '-' ?></span>
                        </div>

                        <a href="os_view.php?id=<?= $os['id'] ?>" class="btn btn-outline-primary btn-sm w-100 mt-2">
                            <i class="bi bi-eye"></i> Ver Detalhes
                        </a>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>
                <p class="text-muted">Nenhuma OS futura encontrada.</p>
            <?php endif; ?>

        </div>
    </div>

</div>

<script>
// FILTRO EM TEMPO REAL
document.getElementById("buscaOs").addEventListener("input", function(){
    const termo = this.value.toLowerCase();
    document.querySelectorAll(".os-card").forEach(card => {
        card.style.display = card.innerText.toLowerCase().includes(termo) ? "" : "none";
    });
});
</script>

<?php include "layout_footer.php"; ?>
