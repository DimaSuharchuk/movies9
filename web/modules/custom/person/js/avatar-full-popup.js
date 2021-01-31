(Drupal => {
  Drupal.behaviors.openAvatarFullSize = {
    attach: context => {
      context.querySelectorAll(".field-gallery img").forEach(img => {
        img.addEventListener("click", () => (new CustomColorbox(img.dataset.full_image)).add());
      });
    },
  };

})(Drupal);
