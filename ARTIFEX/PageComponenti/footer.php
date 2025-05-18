<?php
//Avvia una sessione se non è stata inviata
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4 mb-lg-0">
                <h5 class="footer-heading">Artifex</h5>
                <p>Specialisti in visite guidate di alto livello per i siti culturali e storici più importanti d'Italia. La nostra missione è rendere accessibile e memorabile il patrimonio artistico italiano.</p>
                <div class="mt-4">
                    <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                <h5 class="footer-heading">Esplora</h5>
                <a href="index.php" class="footer-link">Home</a>
                <a href="visite.php" class="footer-link">Visite</a>
                <a href="guide.php" class="footer-link">Guide</a>
                <a href="contatti.php" class="footer-link">Contatti</a>
                <a href="index.php" class="footer-link">Chi Siamo</a>
            </div>
            <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                <h5 class="footer-heading">Utili</h5>
                <a href="index.php" class="footer-link">FAQ</a>
                <a href="index.php" class="footer-link">Termini e Condizioni</a>
                <a href="index.php" class="footer-link">Privacy Policy</a>
                <a href="index.php" class="footer-link">Cookie Policy</a>
            </div>
            <div class="col-lg-4 col-md-4">
                <h5 class="footer-heading">Contatti</h5>
                <p><i class="fas fa-map-marker-alt me-2"></i> Via Alcide de Gasperi, 45100 Rovigo, Italia</p>
                <p><i class="fas fa-phone me-2"></i> +39 04251088</p>
                <p><i class="fas fa-envelope me-2"></i> artifex@gmail.com</p>
            </div>
        </div>
        <hr class="mt-4 mb-4" style="border-color: rgba(255, 255, 255, 0.1);">
        <div class="row">
            <div class="col-md-12 text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Artifex by Xiaqiang Qiu 5F ITIS VIOLA MARCHESINI</p>
            </div>
        </div>
    </div>
</footer>

<!-- jQuery required by Bootstrap -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide alert messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Add animation for cards on hover
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.classList.add('card-hover');
            });
            card.addEventListener('mouseleave', function() {
                this.classList.remove('card-hover');
            });
        });
    });
</script>