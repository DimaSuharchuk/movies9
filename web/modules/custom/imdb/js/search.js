(function () {
  const searchWrapper = document.getElementById("search-desktop-wrapper");
  if (!searchWrapper) {
    return;
  }

  const input = searchWrapper.querySelector("input");
  const reset = searchWrapper.querySelector("#reset-search-input");
  const searchTypeSelect = searchWrapper.querySelector("select");
  const searchResults = searchWrapper.querySelector("#search-results");

  /*
   * Input field events.
   */
  input.addEventListener("input", e => {
    switch (e.inputType) {
      case "insertText":
      case "insertFromPaste":
        if (localStorage.ajaxSubmit) {
          clearTimeout(localStorage.ajaxSubmit);
        }
        localStorage.ajaxSubmit = setTimeout(function () {
          input.blur();
          localStorage.ajaxSubmit = null;
        }, 1500);

        // Animations.
        showResetButton();
        break;
    }
  });

  input.addEventListener("keydown", e => {
    if (e.key === "Enter") {
      e.preventDefault();
      input.blur();
    }
    if (e.key === "Escape") {
      hideSearchResults();
      input.blur();
    }
    if (e.key === "Backspace" && input.value.length === 1) {
      hideResetButton();
    }
  });

  input.addEventListener("focus", () => {
    showSearchResults();
  });

  /*
   * Select list events.
   */
  searchTypeSelect.addEventListener("focus", () => {
    searchTypeSelect.blur();
    input.focus();
  });

  /*
   * Reset button events.
   */
  reset.addEventListener("click", () => {
    input.value = "";
    input.blur();
    searchTypeSelect.value = "multi";
    searchResults.innerHTML = "";
    hideResetButton();
  });

  /*
   * Document events.
   */
  document.body.addEventListener("click", e => {
    if (e.target === input) {
      return;
    }

    if (searchResults.innerHTML.length > 0) {
      hideSearchResults();
    }
  });


  /*
   * Custom functions.
   */
  function hideSearchResults() {
    searchResults.style.display = "none";
  }

  function showSearchResults() {
    searchResults.style.display = "block";
  }

  function showResetButton() {
    searchTypeSelect.classList.add("active");
    reset.classList.add("active");
  }

  function hideResetButton() {
    reset.classList.remove("active");
    searchTypeSelect.classList.remove("active");
  }
})();
