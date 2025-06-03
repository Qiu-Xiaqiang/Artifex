document.getElementById('numero_carta').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
    if (formattedValue.length > 19) formattedValue = formattedValue.substring(0, 19);
    this.value = formattedValue;
});

    // Formattazione scadenza
    document.getElementById('scadenza').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) {
    value = value.substring(0, 2) + '/' + value.substring(2, 4);
}
    this.value = value;
});

    // Solo numeri per CVV
    document.getElementById('cvv').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});

    // Validazione form
    document.getElementById('form-pagamento').addEventListener('submit', function(e) {
    const numeroCarta = document.getElementById('numero_carta').value.replace(/\s/g, '');
    const scadenza = document.getElementById('scadenza').value;
    const cvv = document.getElementById('cvv').value;

    if (numeroCarta.length < 13 || numeroCarta.length > 19) {
    e.preventDefault();
    alert('Il numero della carta deve essere tra 13 e 19 cifre.');
    return;
}

    if (!/^\d{2}\/\d{2}$/.test(scadenza)) {
    e.preventDefault();
    alert('Formato scadenza non valido (MM/AA).');
    return;
}

    if (cvv.length < 3 || cvv.length > 4) {
    e.preventDefault();
    alert('CVV deve essere di 3 o 4 cifre.');
    return;
}

    // Conferma finale
    if (!confirm('Confermi di voler procedere con il pagamento di €<?php echo number_format($totale, 2); ?>?')) {
    e.preventDefault();
}
});

