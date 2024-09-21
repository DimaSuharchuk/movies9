(Drupal => {
  Drupal.behaviors.copyMovieAdminInfo = {
    attach: context => {
      once('copy-info-processed', '.field-copy-movie-admin-info input', context).forEach(el => {
        el.addEventListener('click', () => {
          const imdbId = document.querySelector('.node-wrapper .field-imdb-id .content')
          const title = document.querySelector('.node-wrapper .field-original-title')
          const title2 = document.querySelector('.node-wrapper .field-title') // When site is on Eng language.
          const year = document.querySelector('.node-wrapper .field-movie-release-date .content')
          const years = document.querySelector('.node-wrapper .field-tv-release-years .content')
          const result = []

          if (!imdbId || !title && !title2) {
            return;
          }

          result.push(imdbId.textContent.trim())

          if (title) {
            result.push(title.textContent.trim())
          }
          else {
            result.push(title2.textContent.trim())
          }

          if (year) {
            result.push(year.textContent.trim().split(' ').pop())
          } else if (years) {
            result.push(years.textContent.trim())
          }

          copyToClipboard(el, result.join('\t'));
        })
      })
    }
  };

  function copyToClipboard(el, text) {
    if (!el || !text) {
      return;
    }

    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();

    try {
      document.execCommand('copy');
      el.classList.add('clicked');
    } catch (err) {
      console.error('Помилка при копіюванні в буфер обміну', err);
    }

    document.body.removeChild(textarea);
  }
})(Drupal);
