<?php
require "config.php";

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include "layout_header.php";

// Busca inicial
$produtos = $conn->query("SELECT * FROM produtos ORDER BY nome ASC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3>📦 Gerenciar Produtos</h3>
    <a href="produto_add.php" class="btn btn-success btn-sm">➕ Novo Produto</a>
</div>

<?php if (isset($_SESSION['msg'])): ?>
    <div class="alert alert-info"><?= htmlspecialchars($_SESSION['msg']); unset($_SESSION['msg']); ?></div>
<?php endif; ?>

<!-- Campo de busca -->
<div class="mb-3 position-relative">
    <input type="text" id="buscaProduto" class="form-control shadow-sm"
           placeholder="🔍 Buscar produto por nome, unidade ou preço...">
</div>

<!-- Tabela -->
<div class="table-responsive">
    <table class="table table-striped table-hover align-middle" id="tabelaProdutos">
        <thead class="table-dark text-center">
            <tr>
                <th>Imagem</th>
                <th>Nome</th>
                <th>Unidade</th>
                <th>Preço Venda</th>
                <th width="160">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($produtos && $produtos->num_rows > 0): ?>
                <?php while ($p = $produtos->fetch_assoc()): 

                    // Imagem
                    $img = (!empty($p['imagem']))
                        ? "https://servicos.playtvtech.xyz/uploads/produtos/" . htmlspecialchars($p['imagem'])
                        : "https://via.placeholder.com/60?text=Sem+Foto";

                    // Preço de venda (garante que nunca será null)
                    $precoVenda = isset($p['preco_venda']) ? floatval($p['preco_venda']) : 0.00;

                ?>
                <tr class="text-center linha-produto">
                    <td><img src="<?= $img ?>" style="width:60px; border-radius:6px;"></td>
                    <td class="nome-produto"><?= htmlspecialchars($p['nome']) ?></td>
                    <td><?= htmlspecialchars($p['unidade']) ?></td>

                    <td>R$ <?= number_format($precoVenda, 2, ",", ".") ?></td>

                    <td>
                        <a href="produto_edit.php?id=<?= $p['id'] ?>" class="btn btn-warning btn-sm">✏️ Editar</a>
                        <a href="produto_delete.php?id=<?= $p['id'] ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('Deseja realmente excluir este produto?')">🗑️ Excluir</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-center text-muted py-3">Nenhum produto cadastrado</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
#buscaProduto {
    max-width: 400px;
    border-radius: 10px;
}
.table td {
    vertical-align: middle;
}
</style>

<script>
// ====== BUSCA DINÂMICA ======
document.getElementById('buscaProduto').addEventListener('input', function() {
    const termo = this.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    const linhas = document.querySelectorAll('#tabelaProdutos tbody .linha-produto');
    let encontrou = false;

    linhas.forEach(linha => {
        const texto = linha.textContent.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        if (texto.includes(termo)) {
            linha.style.display = '';
            encontrou = true;
        } else {
            linha.style.display = 'none';
        }
    });

    if (!encontrou) {
        if (!document.getElementById('semResultados')) {
            const tr = document.createElement('tr');
            tr.id = 'semResultados';
            tr.innerHTML = `<td colspan="5" class="text-center text-muted py-3">Nenhum produto encontrado.</td>`;
            document.querySelector('#tabelaProdutos tbody').appendChild(tr);
        }
    } else {
        const msg = document.getElementById('semResultados');
        if (msg) msg.remove();
    }
});
</script>

<?php include "layout_footer.php"; ?>
