</main>
    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> PLAY BOT. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach((row, index) => {
            row.classList.add('table-row-animate');
            // Delay progressivo (efeito cascata), máximo 20 itens
            if (index < 20) {
                row.style.animationDelay = (index * 0.05) + 's';
            } else {
                row.style.opacity = 1; 
            }
        });
    });
    </script>
</body>
</html>