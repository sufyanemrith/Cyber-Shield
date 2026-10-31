/* GET HTML ELEMENTS */
const passwordInput = document.getElementById("password");
const togglePassword = document.getElementById("toggle-password");
const eyeIcon = document.getElementById("eyeIcon");
const passwordMessage = document.getElementById("passwordMessage");
const signupForm = document.querySelector(".signup-container");

/* 1. PASSWORD COMPLEXITY + REAL-TIME VALIDATION */
function validatePassword() {
    const password = passwordInput.value;
    const errors = [];

    if (password.length < 8) {
        errors.push("At least 8 characters");
    }

    // Match PHP's 72-byte limit for bcrypt passwords.
    if (new TextEncoder().encode(password).length > 72) {
        errors.push("Maximum 72 bytes");
    }

    if (!/[A-Z]/.test(password)) {
        errors.push("Uppercase letter (A-Z)");
    }

    if (!/[a-z]/.test(password)) {
        errors.push("Lowercase letter (a-z)");
    }

    if (!/[0-9]/.test(password)) {
        errors.push("Numbers (0-9)");
    }

    if (!/[@#$*&!]/.test(password)) {
        errors.push("Special character (@#$*&!)");
    }

    if (password.length === 0) {
        passwordMessage.textContent = "";
        passwordMessage.className = "password-message";
        return false;
    }

    if (errors.length > 0) {
        passwordMessage.textContent = "Requirements: " + errors.join(", ");
        passwordMessage.className = "password-message error";
        return false;
    }

    passwordMessage.textContent = "Password meets all requirements!";
    passwordMessage.className = "password-message success";
    return true;
}

passwordInput.addEventListener("input", validatePassword);

/* 2. SHOW / HIDE PASSWORD (USABILITY) */
togglePassword.addEventListener("click", function() {
    const isHidden = passwordInput.type === "password";

    passwordInput.type = isHidden ? "text" : "password";
    eyeIcon.className = isHidden ? "bx bx-hide" : "bx bx-show";

    togglePassword.setAttribute(
        "aria-label",
        isHidden ? "Hide password" : "Show password"
    );

    togglePassword.setAttribute(
        "aria-pressed",
        isHidden ? "true" : "false"
    );
});

/* 3. CHECK PASSWORD BEFORE SUBMITTING */
signupForm.addEventListener("submit", function(event) {
    if (!validatePassword()) {
        event.preventDefault();
        passwordInput.focus();
    }
});
