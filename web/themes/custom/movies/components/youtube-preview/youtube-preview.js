((Drupal, once) => {
  'use strict';

  Drupal.behaviors.youtubePreview = {
    attach(context) {
      once('youtubePreview', '.youtube-preview', context).forEach((preview) => {
        preview.addEventListener('click', () => {
          this.loadVideo(preview);
        });
      });
    },

    loadVideo(preview) {
      const videoId = preview.dataset.videoId;
      const language = preview.dataset.language || 'uk';

      if (!videoId) {
        return;
      }

      const params = new URLSearchParams({
        rel: '0',
        controls: '1',
        color: 'white',
        iv_load_policy: '3',
        hl: language,
        autoplay: '1',
        playsinline: '1',
        vq: 'hd720',
      });

      const iframe = document.createElement('iframe');

      iframe.className = 'youtube-preview__iframe';
      iframe.width = '1280';
      iframe.height = '720';
      iframe.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(videoId)}?${params.toString()}`;
      iframe.title = preview.getAttribute('aria-label') || 'YouTube video player';
      iframe.allowFullscreen = true;
      iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';

      preview.replaceWith(iframe);
    },
  };
})(Drupal, once);
