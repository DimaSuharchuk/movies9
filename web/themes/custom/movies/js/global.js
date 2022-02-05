(D => {
  "use strict";

  D.behaviors.languageSwitcher = {
    attach: (context, settings) => {
      const trigger = document.querySelector("#block-language-switcher .active-language");
      if (trigger && !settings.menuOnce) {
        settings.menuOnce = true;
        trigger.addEventListener("click", function () {
          this.nextElementSibling.classList.toggle("open");
        });
      }
    },
  };

  D.behaviors.hamburger = {
    attach: context => {
      const hamburger = context.querySelector(".hamburger");
      if (hamburger) {
        hamburger.addEventListener("click", function () {
          this.classList.toggle("open");
          this.nextElementSibling.classList.toggle("open");
        });
      }
    },
  };

  D.behaviors.themeSwitcher = {
    attach: (context, settings) => {
      if (!settings.themeSwitcherOnce) {
        settings.themeSwitcherOnce = true;

        document.querySelectorAll(".theme-switcher").forEach(wrapper => {
          wrapper.addEventListener("click", () => {
            if (localStorage.getItem("dark-theme") !== "1") {
              Drupal.behaviors.themeSwitcher.setDark();
            }
            else {
              Drupal.behaviors.themeSwitcher.setLight();
            }
          });
        });
      }
    },
    setDark: () => {
      document.body.dataset.theme = "dark";
      localStorage.setItem("dark-theme", "1");
    },
    setLight: () => {
      document.body.dataset.theme = "";
      localStorage.removeItem("dark-theme");
    },
  };

  D.behaviors.redirects = {
    attach: () => {
      document.querySelectorAll("[data-redirect]:not(.processed)").forEach(article => {
        article.classList.add("processed");

        article.addEventListener("click", evt => {
          // Click with ctrl.
          if (evt.ctrlKey) {
            window.open(article.dataset.redirect, '_blank');
          }
          // Left click.
          else {
            location.href = article.dataset.redirect;
          }
        });

        // Middle button click.
        article.addEventListener("auxclick", () => {
          window.open(article.dataset.redirect, '_blank');
        });
      });
    }
  };

})(Drupal);
