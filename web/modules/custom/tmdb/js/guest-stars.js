(($, Drupal) => {
  Drupal.behaviors.guestStarsAccordion = {
    attach: context => {
      $(".guest-stars-label", context).on("click", function () {
        $(this).siblings('.guest-stars-content').slideToggle();
      });
    }
  };

})(jQuery, Drupal);
