(Drupal => {
  Drupal.behaviors.setVideoHeight = {
    attach: context => {
      context.querySelectorAll(".videos-wrapper iframe").forEach(video =>
        video.style.height = video.offsetWidth * 0.5625 + 'px'
      );
    }
  }

})(Drupal);
