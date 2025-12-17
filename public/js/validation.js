const form = document.querySelector("form");
const emailInput = form.querySelector('input[name="email"]');
const confirmedPasswordInput = form.querySelector('input[name="confirmedPassword"]');

function isEmail(email) {
    return /\S+@\S+\.\S+/.test(email);
}

function arePasswordsSame(password, confirmedPassword) {
    return password === confirmedPassword;
}

function markValidation(element, condition) {
    !condition ? element.classList.add('no-valid') : element.classList.remove('no-valid');
}

function debounce(func, timeout = 1000){
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => { func.apply(this, args); }, timeout);
    };
}

emailInput.addEventListener('keyup', debounce(function () {
    markValidation(emailInput, isEmail(emailInput.value));
}));

confirmedPasswordInput.addEventListener('keyup', debounce(function () {
    const condition = arePasswordsSame(
        form.querySelector('input[name="password"]').value,
        confirmedPasswordInput.value
    );
    markValidation(confirmedPasswordInput, condition);
}));