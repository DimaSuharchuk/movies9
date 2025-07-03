((D, once) => {
  "use strict";

  D.behaviors.languageSwitcher = {
    attach: context => {
      const [languageSwitcher] = once('desktop-button--language-switcher', '#block-language-switcher .active-language', context)

      if (languageSwitcher) {
        languageSwitcher.addEventListener("click", function () {
          this.nextElementSibling.classList.toggle("open");
        });
      }
    },
  };

  D.behaviors.hamburger = {
    attach: context => {
      const [hamburger] = once('mobile-button--hamburger-menu', '.hamburger', context)

      if (hamburger) {
        hamburger.addEventListener("click", function () {
          this.classList.toggle("open");
          this.nextElementSibling.classList.toggle("open");
        });
      }
    },
  };

  D.behaviors.redirects = {
    attach: () => {
      once('teaser-redirect', '[data-redirect]').forEach(article => article.addEventListener('mousedown', evt => {
        evt.preventDefault();

        switch (evt.which) {
          // Left click.
          case 1:
            // Click with ctrl.
            if (evt.ctrlKey) {
              window.open(article.dataset.redirect, '_blank');
            } else {
              location.href = article.dataset.redirect;
            }
            break;

          // Middle click.
          case 2:
            window.open(article.dataset.redirect, '_blank');
            break;
        }
      }));
    }
  };

})(Drupal, once);
