(Drupal => {
  Drupal.behaviors.nodeImdbRatingUpdate = {
    attach: () => {
      document.querySelectorAll(".js--node-imdb-rating-placeholder").forEach(placeholder => {
        const bundle = placeholder.getAttribute("data-bundle");
        const tmdb_id = placeholder.getAttribute("data-id");

        const url = `/field/imdb-rating/node/${bundle}/${tmdb_id}`;
        // AJAX replace.
        ajaxReplaceImdbRatingPlaceholder(placeholder, url);
      });
    }
  };
  Drupal.behaviors.nodeOriginalTitleUpdate = {
    attach: () => {
      document.querySelectorAll(".js--node-original-title-placeholder").forEach(placeholder => {
        const bundle = placeholder.getAttribute("data-bundle");
        const tmdb_id = placeholder.getAttribute("data-id");

        const url = `/field/original-title/node/${bundle}/${tmdb_id}`;
        // AJAX replace.
        ajaxReplaceOriginalTitlePlaceholder(placeholder, url);
      });
    }
  };

  Drupal.behaviors.seasonOriginalTitleUpdate = {
    attach: () => {
      document.querySelectorAll(".js--season-original-title-placeholder").forEach(placeholder => {
        const tv_tmdb_id = placeholder.getAttribute("data-tmdb_id");
        const season = placeholder.getAttribute("data-season");

        const url = `/field/original-title/season/${tv_tmdb_id}/${season}`;
        // AJAX replace.
        ajaxReplaceOriginalTitlePlaceholder(placeholder, url);
      });
    }
  };

  Drupal.behaviors.episodeImdbRatingUpdate = {
    attach: () => {
      document.querySelectorAll(".js--episode-imdb-rating-placeholder").forEach(placeholder => {
        const tv_tmdb_id = placeholder.getAttribute("data-tmdb_id");
        const season = placeholder.getAttribute("data-season");
        const episode = placeholder.getAttribute("data-episode");

        const url = `/field/imdb-rating/episode/${tv_tmdb_id}/${season}/${episode}`;
        // AJAX replace.
        ajaxReplaceImdbRatingPlaceholder(placeholder, url);
      });
    }
  };
  Drupal.behaviors.episodeOriginalTitleUpdate = {
    attach: () => {
      document.querySelectorAll(".js--episode-original-title-placeholder").forEach(placeholder => {
        const tv_tmdb_id = placeholder.getAttribute("data-tmdb_id");
        const season = placeholder.getAttribute("data-season");
        const episode = placeholder.getAttribute("data-episode");

        const url = `/field/original-title/episode/${tv_tmdb_id}/${season}/${episode}`;
        // AJAX replace.
        ajaxReplaceOriginalTitlePlaceholder(placeholder, url);
      });
    }
  };


  /**
   * Get IMDb rating from prepared url, prepare html for field "imdb rating"
   * and replace placeholder with that html.
   *
   * @param placeholder
   *   DOM element of placeholder to replace.
   * @param url
   *   Url for AJAX request.
   */
  const ajaxReplaceImdbRatingPlaceholder = (placeholder, url) => {
    const request = new XMLHttpRequest();
    request.open("GET", url);
    request.onload = function () {
      if (this.status >= 200 && this.status < 400) {
        if (isNumeric(this.response) && this.response !== "0") {
          // Replace placeholder with IMDb rating.
          placeholder.replaceWith(buildImdbRatingFieldHtml(this.response));
        }
      }
    };
    request.send();
  }

  /**
   * Get original title from prepared url, prepare html for field "original
   * title" and replace placeholder with that html.
   *
   * @param placeholder
   *   DOM element of placeholder to replace.
   * @param url
   *   Url for AJAX request.
   */
  function ajaxReplaceOriginalTitlePlaceholder(placeholder, url) {
    const request = new XMLHttpRequest();
    request.open("GET", url);
    request.onload = function () {
      if (this.status >= 200 && this.status < 400) {
        if (typeof this.response === "string") {
          // Replace placeholder with IMDb rating.
          placeholder.replaceWith(buildOriginalTitleFieldHtml(this.response.slice(1, -1)));
        }
      }
    };
    request.send();
  }


  /**
   * Build html for field "IMDb rating".
   *
   * @param ratingNumber
   *   IMDb rating - decimal number.
   * @returns {HTMLDivElement}
   */
  const buildImdbRatingFieldHtml = ratingNumber => {
    // Create wrapper.
    const fieldWithLabel = document.createElement("div");
    fieldWithLabel.classList.add("field", "field-with-label");
    // Create label.
    const label = document.createElement("div");
    label.classList.add("label");
    label.textContent = "imdb:";
    // Create rating.
    const content = document.createElement("div");
    content.classList.add("content");
    content.textContent = ratingNumber;
    // Set children to wrapper.
    fieldWithLabel.appendChild(label);
    fieldWithLabel.appendChild(content);

    return fieldWithLabel;
  }
  /**
   * Build html for field "Original title".
   *
   * @param title
   *   Original title field value.
   * @returns {HTMLDivElement}
   */
  const buildOriginalTitleFieldHtml = title => {
    const tmdbField = document.createElement("div");
    tmdbField.classList.add("field", "field-original-title");
    tmdbField.textContent = title;

    return tmdbField;
  }


  /**
   * Helper function: Check is the string can be a number.
   *
   * @param n
   *   String to check
   * @return boolean
   *   String is a number.
   */
  const isNumeric = n => !isNaN(parseFloat(n)) && isFinite(n);
})(Drupal);
