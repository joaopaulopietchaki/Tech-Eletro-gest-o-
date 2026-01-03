<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

include 'layout_header.php';

$ini = $_GET['ini'] ?? date('Y-m-01');
$fim = $_GET['fim'] ?? date('Y-m-t');
$tipo = $_GET['tipo'] ?? 'servicos';

// Relatório de Serviços
if ($tipo === 'servicos') {
    $sql = "SELECT * FROM servicos WHERE data BETWEEN ? AND ? ORDER BY data DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $ini, $fim);
    $stmt->execute();
    $resultado = $stmt->get_result();
}

// Relatório de Clientes
if ($tipo === 'clientes') {
    $sql = "SELECT nome_cliente, COUNT(*) as total_servicos, SUM(valor_recebido) as total_valor 
            FROM servicos WHERE data BETWEEN ? AND ? 
            GROUP BY nome_cliente ORDER BY total_valor DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $ini, $fim);
    $stmt->execute();
    $resultado = $stmt->get_result();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3>📊 Relatórios</h3>
    <a href="export_csv.php?tipo=<?= $tipo ?>&ini=<?= $ini ?>&fim=<?= $fim ?>" class="btn btn-success">
        <i class="bi bi-file-earmark-excel"></i> Exportar CSV
    </a>
</div>

<div class="card p-3 mb-4">
    <form class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Tipo</label>
            <select name="tipo" class="form-control">
                <option value="servicos" <?= $tipo === 'servicos' ? 'selected' : '' ?>>Serviços</option>
                <option value="clientes" <?= $tipo === 'clientes' ? 'selected' : '' ?>>Clientes</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Data Início</label>
            <input type="date" name="ini" class="form-control" value="<?= $ini ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Data Fim</label>
            <input type="date" name="fim" class="form-control" value="<?= $fim ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">&nbsp;</label>
            <button type="submit" class="btn btn-primary d-block">Filtrar</button>
        </div>
    </form>
</div>

<?php if ($tipo === 'servicos'): ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Data</th>
                <th>Cliente</th>
                <th>Serviço</th>
                <th>Valor</th>
                <th>Custo</th>
                <th>Lucro</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_valor = $total_custo = $total_lucro = 0;
            while ($row = $resultado->fetch_assoc()): 
                $total_valor += $row['valor_recebido'];
                $total_custo += $row['custo_total'];
                $total_lucro += $row['lucro'];
            ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($row['data'])) ?></td>
                <td><?= htmlspecialchars($row['nome_cliente']) ?></td>
                <td><?= htmlspecialchars($row['servico_executado']) ?></td>
                <td>R$ <?= number_format($row['valor_recebido'], 2, ',', '.') ?></td>
                <td>R$ <?= number_format($row['custo_total'], 2, ',', '.') ?></td>
                <td>R$ <?= number_format($row['lucro'], 2, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr class="table-info fw-bold">
                <td colspan="3">TOTAIS</td>
                <td>R$ <?= number_format($total_valor, 2, ',', '.') ?></td>
                <td>R$ <?= number_format($total_custo, 2, ',', '.') ?></td>
                <td>R$ <?= number_format($total_lucro, 2, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>
</div>

<?php elseif ($tipo === 'clientes'): ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Total de Serviços</th>
                <th>Valor Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_geral = 0;
            while ($row = $resultado->fetch_assoc()): 
                $total_geral += $row['total_valor'];
            ?>
            <tr>
                <td><?= htmlspecialchars($row['nome_cliente']) ?></td>
                <td><?= $row['total_servicos'] ?></td>
                <td>R$ <?= number_format($row['total_valor'], 2, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr class="table-info fw-bold">
                <td colspan="2">TOTAL GERAL</td>
                <td>R$ <?= number_format($total_geral, 2, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>
</div>
<?php endif; ?>

<?php include 'layout_footer.php'; ?>