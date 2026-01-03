const form = document.querySelector("form");
const emailInput = form.querySelector('input[name="email"]');
const passwordInput = form.querySelector('input[name="password"]');
const confirmedPasswordInput = form.querySelector('input[name="password_confirm"]');
const nameInput = form.querySelector('input[name="name"]');
const surnameInput = form.querySelector('input[name="surname"]');

// Funkcja pomocnicza do oznaczania pola (czerwone/zielone)
function markValidation(element, condition) {
    // Znajdź lub stwórz element na komunikat błędu (opcjonalne, ale w PDF często wymagane)
    // W tym prostym wariancie operujemy ramkami:
    if (!condition) {
        element.classList.add('no-valid');
        element.classList.remove('is-valid');
    } else {
        element.classList.remove('no-valid');
        element.classList.add('is-valid');
    }
}

// Walidacja Emaila
function validateEmail() {
    setTimeout(function () {
        const email = emailInput.value;
        const valid = /\S+@\S+\.\S+/.test(email);
        markValidation(emailInput, valid);
    }, 1000); // Prosty delay (debounce symulowany)
}

// Walidacja Hasła (min 6 znaków)
function validatePassword() {
    setTimeout(function () {
        const password = passwordInput.value;
        const valid = password.length >= 6;
        markValidation(passwordInput, valid);
        // Przy okazji sprawdź zgodność powtórzenia
        validateConfirmPassword();
    }, 500);
}

// Walidacja Powtórzenia Hasła
function validateConfirmPassword() {
    setTimeout(function () {
        const valid = confirmedPasswordInput.value === passwordInput.value && confirmedPasswordInput.value !== '';
        markValidation(confirmedPasswordInput, valid);
    }, 500);
}

// Walidacja Imienia/Nazwiska (min 2 znaki, tylko litery - w tym polskie)
function validateName(input) {
    setTimeout(function () {
        const value = input.value;
        // Regex: Tylko litery (polskie też), spacje i myślniki. Min 2 znaki.
        const regex = /^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ -]{2,}$/;
        const valid = regex.test(value);
        markValidation(input, valid);
    }, 500);
}

// --- DEBOUNCE (Wymóg z PDF) ---
// Funkcja opóźniająca wykonanie walidacji, żeby nie mrugało przy każdym znaku
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this, args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
}

// Podpięcie zdarzeń z użyciem Debounce
// Dzięki temu walidacja odpali się dopiero jak użytkownik przestanie pisać przez 500ms
emailInput.addEventListener('keyup', debounce(validateEmail, 500));
passwordInput.addEventListener('keyup', debounce(validatePassword, 500));
confirmedPasswordInput.addEventListener('keyup', debounce(validateConfirmPassword, 500));

nameInput.addEventListener('keyup', debounce(() => validateName(nameInput), 500));
surnameInput.addEventListener('keyup', debounce(() => validateName(surnameInput), 500));