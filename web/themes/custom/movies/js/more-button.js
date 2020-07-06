(Drupal => {
  Drupal.behaviors.ajaxMoreButtonHeightFit = {
    attach: context => {
      const button = (context.classList && context.classList.contains("more-button-wrapper") && context)
        || context.querySelector(".more-button-wrapper");

      if (button) {
        button.style.height = (button.offsetWidth * 1.5) + "px";
      }
    }
  }

})(Drupal);
