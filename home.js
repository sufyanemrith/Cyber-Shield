"use strict";

(function () {
    const form = document.getElementById("searchForm");
    const input = document.getElementById("searchInput");
    const error = document.getElementById("searchError");

    // Leave other pages unaffected if the search form is missing.
    if (!form || !input || !error) return;

    // Same allowed characters as clean_search() in function.php.
    const allowed = /^[\p{L}\p{N}\s\-'.]+$/u;

    function showError(message) {
        error.textContent = message;
        input.setAttribute("aria-invalid", message ? "true" : "false");
    }

    input.addEventListener("input", function () {
        showError("");
    });

    form.addEventListener("submit", function (event) {
        const value = input.value.trim();
        input.value = value;
        let message = "";

        if (value === "") {
            message = "Enter a medicine name to search.";
        } else if (Array.from(value).length > 80) {
            message = "Search must be 80 characters or fewer.";
        } else if (!allowed.test(value)) {
            message = "Use letters, numbers, spaces, hyphens, apostrophes or periods only.";
        }

        showError(message);

        if (message !== "") {
            event.preventDefault();
            input.focus();
        }
        // On success, the browser submits q to products.html using GET.
    });
})();
