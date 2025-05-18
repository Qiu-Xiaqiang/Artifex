document.addEventListener('DOMContentLoaded', function() {
    // Filtro per stato prenotazioni
    const statusFilter = document.getElementById('status-filter');
    const prenotazioniCards = document.querySelectorAll('.prenotazione-card');

    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            const selectedValue = this.value;

            prenotazioniCards.forEach(card => {
                if (selectedValue === 'all') {
                    card.style.display = 'block';
                } else if (selectedValue === 'future' && !card.classList.contains('past')) {
                    card.style.display = 'block';
                } else if (selectedValue === 'past' && card.classList.contains('past')) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
});