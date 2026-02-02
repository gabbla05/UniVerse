const form = document.querySelector("form");
const emailInput = form.querySelector('input[name="email"]');
const passwordInput = form.querySelector('input[name="password"]');
const confirmedPasswordInput = form.querySelector('input[name="password_confirm"]');
const nameInput = form.querySelector('input[name="name"]');
const surnameInput = form.querySelector('input[name="surname"]');

function markValidation(element, condition) {
    
    if (!condition) {
        element.classList.add('no-valid');
        element.classList.remove('is-valid');
    } else {
        element.classList.remove('no-valid');
        element.classList.add('is-valid');
    }
}

function validateEmail() {
    setTimeout(function () {
        const email = emailInput.value;
        const valid = /\S+@\S+\.\S+/.test(email);
        markValidation(emailInput, valid);
    }, 1000); 
}

function validatePassword() {
    setTimeout(function () {
        const password = passwordInput.value;
        const valid = password.length >= 6;
        markValidation(passwordInput, valid);
        validateConfirmPassword();
    }, 500);
}

function validateConfirmPassword() {
    setTimeout(function () {
        const valid = confirmedPasswordInput.value === passwordInput.value && confirmedPasswordInput.value !== '';
        markValidation(confirmedPasswordInput, valid);
    }, 500);
}

function validateName(input) {
    setTimeout(function () {
        const value = input.value;
        const regex = /^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ -]{2,}$/;
        const valid = regex.test(value);
        markValidation(input, valid);
    }, 500);
}

function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this, args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
}

emailInput.addEventListener('keyup', debounce(validateEmail, 500));
passwordInput.addEventListener('keyup', debounce(validatePassword, 500));
confirmedPasswordInput.addEventListener('keyup', debounce(validateConfirmPassword, 500));

nameInput.addEventListener('keyup', debounce(() => validateName(nameInput), 500));
surnameInput.addEventListener('keyup', debounce(() => validateName(surnameInput), 500));