document.addEventListener('DOMContentLoaded', function() {
    // Gestione tab per persistenza dopo refresh
    const url = window.location.href;
    if (url.indexOf('#sicurezza') !== -1) {
    document.getElementById('security-tab').click();
}

    // Controllo forza password
    const newPasswordInput = document.getElementById('new_password');
    const passwordStrengthMeter = document.getElementById('password-strength-meter');

    if (newPasswordInput && passwordStrengthMeter) {
    newPasswordInput.addEventListener('input', function() {
    const password = this.value;
    let strength = 0;

    // Lunghezza minima
    if (password.length >= 8) {
    strength += 1;
}

    // Lettere maiuscole e minuscole
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) {
    strength += 1;
}

    // Numeri e caratteri speciali
    if (password.match(/[0-9]/) && password.match(/[^a-zA-Z0-9]/)) {
    strength += 1;
}

    // Aggiorna l'indicatore visivo
    passwordStrengthMeter.className = '';
    if (strength === 0) {
    passwordStrengthMeter.classList.add('password-weak');
} else if (strength === 1) {
    passwordStrengthMeter.classList.add('password-weak');
} else if (strength === 2) {
    passwordStrengthMeter.classList.add('password-medium');
} else {
    passwordStrengthMeter.classList.add('password-strong');
}
});
}

    // Verifica corrispondenza password
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordMatchMessage = document.getElementById('password-match-message');

    if (confirmPasswordInput && newPasswordInput && passwordMatchMessage) {
    confirmPasswordInput.addEventListener('input', function() {
    if (this.value === '') {
    passwordMatchMessage.textContent = '';
    passwordMatchMessage.className = 'form-text';
} else if (this.value === newPasswordInput.value) {
    passwordMatchMessage.textContent = 'Le password coincidono';
    passwordMatchMessage.className = 'form-text text-success';
} else {
    passwordMatchMessage.textContent = 'Le password non coincidono';
    passwordMatchMessage.className = 'form-text text-danger';
}
});

    newPasswordInput.addEventListener('input', function() {
    if (confirmPasswordInput.value !== '') {
    if (this.value === confirmPasswordInput.value) {
    passwordMatchMessage.textContent = 'Le password coincidono';
    passwordMatchMessage.className = 'form-text text-success';
} else {
    passwordMatchMessage.textContent = 'Le password non coincidono';
    passwordMatchMessage.className = 'form-text text-danger';
}
}
});
}
});
