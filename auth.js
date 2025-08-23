document.addEventListener('DOMContentLoaded', function () {
    const togglePassword = document.querySelector('.toggle-password');
    const passwordInput = document.getElementById('password');
    const emailInput = document.getElementById('email');

    // Password visibility toggle
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? 'visibility_off' : 'visibility';
        });
    }

    // Function to validate email
    function validateEmail(email) {
        const re = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
        return re.test(String(email).toLowerCase());
    }

    // Function to validate password (minimum 6 characters)
    function validatePassword(password) {
        return password.length >= 6;
    }

    // Add validation feedback
    function addValidationFeedback(inputElement, isValid) {
        const formGroup = inputElement.closest('.form-group');
        if (formGroup) {
            if (isValid) {
                formGroup.classList.add('is-valid');
                formGroup.classList.remove('is-invalid');
            } else {
                formGroup.classList.add('is-invalid');
                formGroup.classList.remove('is-valid');
            }
        }
    }

    // Event listener for email input
    if (emailInput) {
        emailInput.addEventListener('input', function () {
            const isValid = validateEmail(this.value);
            addValidationFeedback(this, isValid);
        });
    }

    // Event listener for password input
    if (passwordInput) {
        passwordInput.addEventListener('input', function () {
            const isValid = validatePassword(this.value);
            addValidationFeedback(this, isValid);
        });
    }
});