((D, settings) => {
  D.behaviors.sortListByRating = {
    attach: context => {
      // For anonymous users
      if (!settings.user.uid) {
        document.querySelectorAll('.imdb-rating-wrapper').forEach(field => {
          D.behaviors.sortListByRating.sort(field)
        })
      }
      // For authorized users.
      else if (context.classList && context.classList.contains('field-with-label')) {
        D.behaviors.sortListByRating.sort(context)
      }
    },

    sort(field) {
      const rating = field.querySelector('.content')?.textContent ?? 0
      const currentTeaser = field.closest('.node-teaser')
      const currentId = currentTeaser.dataset.id

      currentTeaser.dataset.rating = rating

      if (rating === "0") {
        // Remove field completely.
        field.remove()
        return;
      }

      if (rating) {
        const list = field.closest('.items-list-content')

        const processed = list.querySelectorAll('article[data-rating]')
        const filtered = Array.from(processed).filter(article => article.dataset.id !== currentId && parseFloat(article.getAttribute('data-rating')) < rating)

        if (filtered[0]) {
          filtered[0].before(currentTeaser)
        }
      }
    }
  };

})(Drupal, drupalSettings);
