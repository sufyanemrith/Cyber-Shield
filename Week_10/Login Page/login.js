/* GET HTML ELEMENTS */
const passwordInput = document.getElementById("password");
const togglePassword = document.getElementById("toggle-password");
const eyeIcon = document.getElementById("eyeIcon");

const loginForm = document.getElementById("loginForm");
const loginButton = document.getElementById("loginButton");
const loginMessage = document.getElementById("loginMessage");

/* 1. SHOW / HIDE PASSWORD */
togglePassword.addEventListener("click", function () {

    const isHidden = passwordInput.type === "password";

    passwordInput.type = isHidden ? "text" : "password";

    eyeIcon.className = isHidden
        ? "bx bx-hide"
        : "bx bx-show";

    togglePassword.setAttribute(
        "aria-label",
        isHidden ? "Hide password" : "Show password"
    );

    togglePassword.setAttribute(
        "aria-pressed",
        String(isHidden)
    );
});

/* 2. SEND LOGIN TO PHP */
loginForm.addEventListener("submit", async function (event) {

    event.preventDefault();

    // Prevent accidental double submission.
    if (loginButton.disabled) return;

    loginButton.disabled = true;

    loginMessage.textContent = "Checking credentials...";
    loginMessage.style.color = "#ffffff";

    try {

        const response = await fetch(loginForm.action, {

            method: "POST",

            body: new FormData(loginForm),

            credentials: "same-origin",

            headers: {
                "Accept": "application/json"
            }

        });

        /* 3. CHECK THE SERVER RESPONSE */
        // An Apache/PHP error page might contain HTML instead of JSON.
        const contentType =
            response.headers.get("content-type") || "";

        if (!contentType.includes("application/json")) {

            throw new Error(
                "Login endpoint did not return JSON (HTTP " +
                response.status + ")."
            );
        }

        const result = await response.json();

        if (!result || typeof result.message !== "string") {

            throw new Error(
                "Login endpoint returned an unexpected response."
            );
        }

        loginMessage.textContent = result.message;


        /* 4. SUCCESSFUL LOGIN */
        if (response.ok && result.success === true) {

            loginMessage.style.color = "#b9ffcf";

            if (typeof result.redirect !== "string") {

                throw new Error(
                    "Login succeeded but no redirect was supplied."
                );
            }

            window.location.assign(result.redirect);

            return;
        }

        /* 5. FAILED LOGIN */
        passwordInput.value = "";

        loginMessage.style.color = "#ffb3b3";


    } catch (error) {

        loginMessage.textContent =
            "Login could not be completed. " +
            "Check XAMPP and the browser console.";

        loginMessage.style.color = "#ffb3b3";

        console.error("Login request failed:", error);

    } finally {

        loginButton.disabled = false;
    }
});