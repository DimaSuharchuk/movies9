((Drupal, once) => {
  'use strict';

  Drupal.behaviors.themeSwitcher = {
    attach(context) {
      once('themeSwitcher', '.theme-switcher', context).forEach(el => {
        el.addEventListener('click', () => {
          const dark = localStorage.getItem('dark-theme') === '1';

          if (dark) {
            Drupal.behaviors.themeSwitcher.setLight();
          }
          else {
            Drupal.behaviors.themeSwitcher.setDark();
          }
        });
      });
    },
    setDark() {
      document.body.dataset.theme = 'dark';
      localStorage.setItem('dark-theme', '1');
    },
    setLight() {
      document.body.dataset.theme = '';
      localStorage.setItem('dark-theme', '0');
    },
  };
})(Drupal, once);
