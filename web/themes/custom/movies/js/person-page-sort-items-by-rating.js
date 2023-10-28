(D => {
  D.behaviors.sortListByRating = {
    attach: context => {
      if (context.classList && context.classList.contains('field-with-label')) {
        const rating = context.querySelector('.content')?.textContent ?? 0
        const currentTeaser = context.closest('.node-teaser')
        const currentId = currentTeaser.dataset.id

        currentTeaser.dataset.rating = rating

        if (rating === "0") {
          // Remove field completely.
          context.remove()
          return;
        }

        if (rating) {
          const list = context.closest('.items-list-content')

          const processed = list.querySelectorAll('article[data-rating]')
          const filtered = Array.from(processed).filter(article => article.dataset.id !== currentId && parseFloat(article.getAttribute('data-rating')) < rating)

          if (filtered[0]) {
            filtered[0].before(currentTeaser)
          }
        }
      }
    },
  };

})(Drupal);
