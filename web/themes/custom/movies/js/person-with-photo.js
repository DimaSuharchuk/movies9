(D => {
  D.behaviors.personWithPhoto = {
    attach: context => {
      context.querySelectorAll("#with-photo").forEach(checkbox => {
        const personsWithoutPhoto = checkbox.closest(".items-list-wrapper").querySelectorAll(".person.no-photo");

        checkbox.addEventListener("change", function () {
          if (!checkbox.checked) {
            personsWithoutPhoto.forEach(person => person.classList.add("show"));
          }
          else {
            personsWithoutPhoto.forEach(person => person.classList.remove("show"));
          }
        });

        // Remove unnecessary checkbox if all persons have photo.
        if (!personsWithoutPhoto.length) {
          checkbox.parentElement.remove();
        }
      });
    },
  };

})(Drupal);
