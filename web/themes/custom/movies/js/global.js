"use strict";

(D => {
  D.behaviors.languageSwitcher = {
    attach: (context, settings) => {
      settings.menuOnce = false;
      const trigger = context.querySelector(".active-language");
      if (trigger && !settings.menuOnce) {
        settings.menuOnce = true;
        trigger.addEventListener("click", function () {
          this.nextElementSibling.classList.toggle("open");
        });
      }
    },
  }
})(Drupal);
