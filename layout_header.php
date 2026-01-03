<?php
require_once __DIR__ . "/config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header("Location: login.php");
    exit;
}

$EMPRESA_NOME = $EMPRESA_NOME ?? "Tech Eletro";
$USUARIO_NOME = $_SESSION['nome'] ?? "Administrador";
$PAGINA_ATUAL = basename($_SERVER['PHP_SELF']);

$MENU = [
    "dashboard.php" => ["Dashboard", "bi-speedometer2"],
    "os.php" => ["OS", "bi-journal-text"],
    "clientes.php" => ["Clientes", "bi-people"],
    "produtos.php" => ["Produtos", "bi-box-seam"],
    "orcamentos.php" => ["Orçamentos", "bi-receipt"],
    "servicos.php" => ["Serviços", "bi-tools"],
    "socio.php" => ["Sócios", "bi-person-vcard"],
    "configuracoes.php" => ["Configurações", "bi-gear"]
];

if ($PAGINA_ATUAL === "index.php") {
    $PAGINA_ATUAL = "dashboard.php";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

<title><?= htmlspecialchars($EMPRESA_NOME) ?> — Painel</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
:root {
    --topbar-height: 60px;
    --sidebar-width: 250px;
    --primary: #0d6efd;
    --sidebar-bg: #ffffff;
    --sidebar-hover: #eef4ff;
    --sidebar-active: #e2ecff;
    --text-dark: #1d1d1f;
}

body {
    margin: 0;
    background: #f5f7fb;
    font-family: "Segoe UI", sans-serif;
    overflow-x: hidden;
}

/* TOPBAR PREMIUM */
.topbar {
    height: var(--topbar-height);
    background: var(--primary);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 2000;
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
    font-size: 16px;
}

.menu-btn {
    color: #fff;
    font-size: 26px;
    cursor: pointer;
    display: block;
}

.brand {
    font-size: 17px;
    font-weight: 600;
}

/* SIDEBAR PREMIUM */
.sidebar {
    position: fixed;
    top: var(--topbar-height);
    left: 0;
    bottom: 0;
    width: var(--sidebar-width);
    background: var(--sidebar-bg);
    border-right: 1px solid #e1e1e8;
    overflow-y: auto;
    padding-top: 10px;
    transition: transform .25s ease;
    z-index: 1500;
    transform: translateX(-260px);
}

.sidebar.open {
    transform: translateX(0);
}

.sidebar a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    margin: 4px 12px;
    border-radius: 8px;
    font-size: 15px;
    text-decoration: none;
    color: var(--text-dark);
    transition: 0.2s;
}

.sidebar a:hover {
    background: var(--sidebar-hover);
}

.sidebar a.active {
    background: var(--sidebar-active);
    color: var(--primary);
    font-weight: 600;
}

/* CONTENT */
.content {
    padding: calc(var(--topbar-height) + 20px) 20px 40px 20px;
    transition: margin-left .25s ease;
    min-height: 100vh;
}

/* DESKTOP */
@media (min-width: 992px) {
    .menu-btn { display:none; }
    .sidebar {
        transform: translateX(0);
    }
    .content {
        margin-left: var(--sidebar-width);
    }
}

/* Scroll elegante */
.sidebar::-webkit-scrollbar {
    width: 6px;
}
.sidebar::-webkit-scrollbar-thumb {
    background: #c9c9d2;
    border-radius: 3px;
}

</style>
</head>

<body>

<!-- TOPBAR -->
<div class="topbar">
    <span class="menu-btn" id="menu-toggle"><i class="bi bi-list"></i></span>
    <span class="brand"><?= htmlspecialchars($EMPRESA_NOME) ?></span>
    <span><i class="bi bi-person-circle"></i> <?= htmlspecialchars($USUARIO_NOME) ?></span>
</div>

<!-- SIDEBAR -->
<nav class="sidebar" id="sidebar">
<?php foreach ($MENU as $arquivo => $dados): ?>
    <a href="<?= $arquivo ?>" class="<?= ($PAGINA_ATUAL === $arquivo ? 'active' : '') ?>">
        <i class="bi <?= $dados[1] ?> fs-5"></i> <?= $dados[0] ?>
    </a>
<?php endforeach; ?>

    <a href="logout.php" class="text-danger">
        <i class="bi bi-door-open fs-5"></i> Sair
    </a>
</nav>

<!-- CONTENT -->
<div class="content">

<script>
document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("menu-toggle");
    const sb  = document.getElementById("sidebar");

    btn.addEventListener("click", () => sb.classList.toggle("open"));
});
</script>
