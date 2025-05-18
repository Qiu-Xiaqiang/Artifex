document.addEventListener('DOMContentLoaded', function() {
    // Gestione del form di prenotazione
    const eventoSelect = document.getElementById('evento-select');
    const quantitaInput = document.getElementById('quantita');
    const prezzoTotaleDisplay = document.getElementById('prezzo-totale');
    const dettagliEvento = document.getElementById('dettagli-evento');
    const dataDisplay = document.getElementById('data-display');
    const linguaDisplay = document.getElementById('lingua-display');
    const prezzoDisplay = document.getElementById('prezzo-display');
    const postiDisplay = document.getElementById('posti-display');

    // Gestione pulsanti "Seleziona" nelle date disponibili
    const selezionaButtons = document.querySelectorAll('.seleziona-evento');
    selezionaButtons.forEach(button => {
    button.addEventListener('click', function() {
    const eventoId = this.getAttribute('data-evento-id');
    const eventoData = this.getAttribute('data-evento-data');
    const eventoPrezzo = this.getAttribute('data-evento-prezzo');
    const eventoLingua = this.getAttribute('data-evento-lingua');
    const eventoPosti = this.getAttribute('data-evento-posti');

    // Imposta il valore nel select
    eventoSelect.value = eventoId;

    // Aggiorna i dettagli visualizzati
    dataDisplay.textContent = eventoData;
    linguaDisplay.textContent = eventoLingua;
    prezzoDisplay.textContent = eventoPrezzo;
    postiDisplay.textContent = eventoPosti;

    // Mostra la sezione dei dettagli
    dettagliEvento.classList.remove('d-none');

    // Aggiorna il prezzo totale
    updateTotalPrice();

    // Scorri fino al form di prenotazione
    document.querySelector('.prenotazione-card').scrollIntoView({
    behavior: 'smooth'
});
});
});

    // Aggiorna i dettagli dell'evento quando viene selezionato
    eventoSelect.addEventListener('change', function() {
    if (this.value) {
    const selectedOption = this.options[this.selectedIndex];
    const prezzo = selectedOption.getAttribute('data-prezzo');
    const posti = selectedOption.getAttribute('data-posti');
    const lingua = selectedOption.getAttribute('data-lingua');

    dataDisplay.textContent = selectedOption.text.split(' - ')[0];
    linguaDisplay.textContent = lingua;
    prezzoDisplay.textContent = prezzo;
    postiDisplay.textContent = posti;

    // Limita la quantità in base ai posti disponibili
    quantitaInput.max = posti;
    if (parseInt(quantitaInput.value) > parseInt(posti)) {
    quantitaInput.value = posti;
}

    dettagliEvento.classList.remove('d-none');
    updateTotalPrice();
} else {
    dettagliEvento.classList.add('d-none');
    prezzoTotaleDisplay.textContent = '0.00€';
}
});

    // Aggiorna il prezzo totale quando cambia la quantità
    quantitaInput.addEventListener('change', updateTotalPrice);
    quantitaInput.addEventListener('input', updateTotalPrice);

    function updateTotalPrice() {
    if (eventoSelect.value) {
    const selectedOption = eventoSelect.options[eventoSelect.selectedIndex];
    const prezzo = parseFloat(selectedOption.getAttribute('data-prezzo'));
    const posti = parseInt(selectedOption.getAttribute('data-posti'));
    let quantita = parseInt(quantitaInput.value);

    // Verifica che la quantità non superi i posti disponibili
    if (quantita > posti) {
    quantita = posti;
    quantitaInput.value = posti;
}

    if (quantita < 1) {
    quantita = 1;
    quantitaInput.value = 1;
}

    const totale = prezzo * quantita;
    prezzoTotaleDisplay.textContent = totale.toFixed(2) + '€';
}
}
});
