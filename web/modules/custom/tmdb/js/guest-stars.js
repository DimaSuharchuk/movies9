(Drupal => {
  Drupal.behaviors.guestStarsAccordion = {
    attach: context => {
      const slideDuration = 500; // .5s

      context.querySelectorAll(".guest-stars-label").forEach(star => {
        star.addEventListener("click", () => {
          slideToggle(star.nextElementSibling, slideDuration);

          star.classList.toggle("open");
          // Slide up processing class.
          if (!star.classList.contains("open")) {
            star.classList.add("processing");
            setTimeout(() => star.classList.remove("processing"), slideDuration);
          }
        });
      });
    }
  };

  const slideDown = (target, duration) => {
    // Transition properties for smooth slide.
    target.style.transitionProperty = "height";
    target.style.transitionDuration = duration + "ms";
    target.style.overflow = "hidden";

    // Calculate block height and set.
    target.style.display = "block";
    const height = target.offsetHeight;
    target.style.height = 0;
    target.offsetHeight;
    target.style.height = height + "px";
  }

  const slideUp = (target, duration) => {
    // All transition properties already defined in slideDown(). And now we
    // should just set zero height for element.
    target.style.height = 0;

    // We have to bring it back how it was before the sliding.
    window.setTimeout(() => {
      target.style.display = "none";
      target.style.removeProperty("height");
    }, duration);
  }

  const slideToggle = (target, duration) => {
    if (window.getComputedStyle(target).display === "none") {
      return slideDown(target, duration);
    }
    else {
      return slideUp(target, duration);
    }
  }

})(Drupal);
