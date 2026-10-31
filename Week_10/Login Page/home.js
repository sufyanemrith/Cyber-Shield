"use strict";

(function () {

    /* 1. GET HTML ELEMENTS */
    const form =
        document.getElementById("searchForm");

    const input =
        document.getElementById("searchInput");

    const error =
        document.getElementById("searchError");

    /* 2. VERIFY ELEMENTS EXIST */
    if (!form || !input || !error) {
        return;
    }

    /* 3. ALLOWED SEARCH CHARACTERS */
    const allowed =
        /^[\p{L}\p{N}\s\-'.]+$/u;


    /* 4. VALIDATE SEARCH FORM */
    form.addEventListener(
        "submit",
        function (event) {

            const value = input.value.trim();

            input.value = value;

            let message = "";

            /* EMPTY SEARCH */
            if (value === "") {

                message =
                    "Enter a medicine name to search.";

            }

            /* SEARCH LENGTH */
            else if (
                Array.from(value).length > 80
            ) {

                message =
                    "Search must be 80 characters or fewer.";

            }

            /* INVALID CHARACTERS */
            else if (!allowed.test(value)) {

                message =
                    "Use letters, numbers, spaces, " +
                    "hyphens, apostrophes or periods only.";

            }

            /* SHOW VALIDATION MESSAGE */
            error.textContent = message;

            /* PREVENT INVALID SUBMISSION */
            if (message !== "") {

                event.preventDefault();

                input.focus();
            }

        }
    );

})();