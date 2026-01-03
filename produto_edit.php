<?php
require "config.php";

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['msg'] = "Produto não encontrado.";
    header("Location: produtos.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$produto = $stmt->get_result()->fetch_assoc();

if (!$produto) {
    $_SESSION['msg'] = "Produto não encontrado.";
    header("Location: produtos.php");
    exit;
}

include "layout_header.php";
?>

<div class="container-fluid">
    <h3>✏️ Editar Produto</h3>

    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-info"><?= htmlspecialchars($_SESSION['msg']); unset($_SESSION['msg']); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" action="produto_edit_salvar.php?id=<?= $id ?>">
        <div class="row mb-3">
            <div class="col-md-6">
                <label>Nome do Produto</label>
                <input type="text" name="nome" class="form-control"
                    value="<?= htmlspecialchars($produto['nome']) ?>" required>
            </div>

            <div class="col-md-3">
                <label>Unidade</label>
                <input type="text" name="unidade" class="form-control"
                    value="<?= htmlspecialchars($produto['unidade']) ?>" placeholder="Ex: un, pç, cx">
            </div>

            <div class="col-md-3">
                <label>Preço de Venda (R$)</label>
                <input type="text" name="preco_venda" id="preco_venda"
                    class="form-control moeda"
                    value="<?= number_format(floatval($produto['preco_venda']), 2, ',', '.') ?>" required>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label>Custo (R$)</label>
                <input type="text" name="preco_custo" id="preco_custo"
                    class="form-control moeda"
                    value="<?= number_format(floatval($produto['preco_custo']), 2, ',', '.') ?>">
            </div>

            <div class="col-md-6">
                <label>Imagem do Produto</label><br>

                <?php if (!empty($produto['imagem'])): ?>
                    <img src="https://servicos.playtvtech.xyz/uploads/produtos/<?= htmlspecialchars($produto['imagem']) ?>"
                        style="width:100px; border-radius:6px; margin-bottom:5px;">
                <?php endif; ?>

                <input type="file" name="imagem" class="form-control">
            </div>
        </div>

        <div class="mb-3">
            <label>Descrição</label>
            <textarea name="descricao" class="form-control" rows="3"><?= htmlspecialchars($produto['descricao']) ?></textarea>
        </div>

        <hr>
        <button type="submit" class="btn btn-primary">💾 Salvar Alterações</button>
        <a href="produtos.php" class="btn btn-secondary">⬅ Voltar</a>
    </form>
</div>

<style>
form label { font-weight: 600; }
input.moeda { text-align: right; }
</style>

<script>
// === Máscara de moeda ===
function aplicarMascaraMoeda(campo) {
    campo.addEventListener('input', (e) => {
        let valor = e.target.value.replace(/\D/g, "");
        valor = (parseInt(valor, 10) / 100).toFixed(2) + '';
        valor = valor.replace(".", ",");
        valor = valor.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        e.target.value = valor;
    });
}

document.querySelectorAll('.moeda').forEach(el => aplicarMascaraMoeda(el));
</script>

<?php include "layout_footer.php"; ?>
