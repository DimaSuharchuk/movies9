"use strict";

(D => {
  D.behaviors.languageSwitcher = {
    attach: (context, settings) => {
      settings.menuOnce = settings.menuOnce || false;
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

})(Drupal);
