<!-- FOOTER PREMIUM -->
</div> <!-- fim do .content -->
    
<footer class="footer-premium text-center mt-4">
    <div class="container">
        <span>
            © <?= date('Y') ?> <?= htmlspecialchars($EMPRESA_NOME ?? "Tech Eletro") ?> — Todos os direitos reservados.
        </span>
    </div>
</footer>

<style>
.footer-premium {
    padding: 20px 10px;
    background: #ffffff;
    color: #555;
    font-size: 14px;
    border-top: 1px solid #e5e5ec;
    margin-top: 40px;
}

@media(min-width: 992px) {
    .footer-premium {
        margin-left: var(--sidebar-width);
    }
}
</style>

<!-- ✅ BOOTSTRAP JS (OBRIGATÓRIO) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>