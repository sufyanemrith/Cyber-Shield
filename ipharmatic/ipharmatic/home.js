"use strict";

(function () {

    /* 1. GET HOMEPAGE SEARCH ELEMENTS */
    const homeSearchForm = document.getElementById("searchForm");
    const homeSearchInput = document.getElementById("searchInput");
    const homeSearchError = document.getElementById("searchError");

    /* 2. CHECK ELEMENTS EXIST */
    if (!homeSearchForm || !homeSearchInput || !homeSearchError) {
        return;
    }

    /* 3. ALLOWED SEARCH CHARACTERS */
    const homeAllowedCharacters = /^[\p{L}\p{N}\s\-'.]+$/u;

    /* 4. VALIDATE THE HOMEPAGE SEARCH */
    homeSearchForm.addEventListener("submit", function (event) {

        const homeSearchValue = homeSearchInput.value.trim();
        homeSearchInput.value = homeSearchValue;

        let homeErrorMessage = "";

        if (homeSearchValue === "") {
            homeErrorMessage = "Enter a medicine name to search.";
        } else if (Array.from(homeSearchValue).length > 80) {
            homeErrorMessage = "Search must be 80 characters or fewer.";
        } else if (!homeAllowedCharacters.test(homeSearchValue)) {
            homeErrorMessage =
                "Use letters, numbers, spaces, " +
                "hyphens, apostrophes or periods only.";
        }

        homeSearchError.textContent = homeErrorMessage;

        if (homeErrorMessage !== "") {
            event.preventDefault();
            homeSearchInput.focus();
        }
    });

    /* 5. CLEAR THE MESSAGE WHEN TYPING AGAIN */
    homeSearchInput.addEventListener("input", function () {
        homeSearchError.textContent = "";
    });

})();
