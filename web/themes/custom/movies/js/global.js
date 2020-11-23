(D => {
  "use strict";

  D.behaviors.languageSwitcher = {
    attach: (context, settings) => {
      const trigger = document.querySelector(".desktop-nav .active-language");
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
            if (Cookies.get("theme-mode") !== "dark") {
              Drupal.behaviors.themeSwitcher.setDark(true);
            }
            else {
              Drupal.behaviors.themeSwitcher.setLight();
            }
          });
        });
      }
    },
    setDark: (updateCookie) => {
      document.body.dataset.theme = "dark";
      if (updateCookie) {
        Cookies.set("theme-mode", "dark", {expires: 365});
      }
    },
    setLight: () => {
      Cookies.set("theme-mode", "");
      document.body.dataset.theme = "";
    },
  };

})(Drupal);
