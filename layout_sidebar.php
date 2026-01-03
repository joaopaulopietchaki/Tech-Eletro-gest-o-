<?php
if (session_status()===PHP_SESSION_NONE) session_start();
$current = basename($_SERVER['PHP_SELF']);
function act($p,$c){$arr=is_array($p)?$p:[$p];return in_array($c,$arr)?'active':'';}
?>
<div class="sidebar" id="sidebar">
  <h6 class="text-center">Menu</h6>
  <ul class="nav flex-column">
    <li class="nav-item"><a class="nav-link <?= act('dashboard.php',$current) ?> text-white" href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i><span>Dashboard</span></a></li>

    <li class="nav-item">
      <a class="nav-link text-white d-flex justify-content-between" data-bs-toggle="collapse" href="#menuOS" role="button" aria-expanded="false"> <span><i class="bi bi-clipboard-check me-2"></i> OS </span> <i class="bi bi-chevron-down"></i></a>
      <div class="collapse <?= (in_array($current,['os.php','os_add.php','os_view.php','os_edit.php'])?'show':'') ?>" id="menuOS">
        <ul class="nav flex-column ms-2">
          <li><a class="nav-link text-white <?= act('os.php',$current) ?>" href="os.php">Todas as OS</a></li>
          <li><a class="nav-link text-white <?= act('os_add.php',$current) ?>" href="os_add.php">Nova OS</a></li>
          <li><a class="nav-link text-white <?= act('os_equipamentos.php',$current) ?>" href="os_equipamentos.php">Equipamentos</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item"><a class="nav-link text-white <?= act('clientes.php',$current) ?>" href="clientes.php"><i class="bi bi-people me-2"></i>Clientes</a></li>
    <li class="nav-item"><a class="nav-link text-white <?= act('produtos.php',$current) ?>" href="produtos.php"><i class="bi bi-box-seam me-2"></i>Produtos</a></li>
    <li class="nav-item"><a class="nav-link text-white <?= act('orcamentos.php',$current) ?>" href="orcamentos.php"><i class="bi bi-file-earmark-text me-2"></i>Orçamentos</a></li>
    <li class="nav-item mt-3"><a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
  </ul>
  <div class="mt-3 text-muted small">Olá, <?= htmlspecialchars($_SESSION['nome'] ?? 'Usuário') ?></div>
</div>

<script>
// toggle sidebar small
document.getElementById('menuToggle')?.addEventListener('click', ()=>{
  document.getElementById('sidebar').classList.toggle('collapsed');
  document.querySelector('.main')?.classList.toggle('collapsed');
});
</script>