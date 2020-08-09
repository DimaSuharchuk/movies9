(Drupal => {
  Drupal.behaviors.extraTabs = {
    attach: context => {
      const header = context.querySelector("#page header");
      const headerHeight = header && header.offsetHeight;
      const staticSections = context.querySelectorAll(".node section");
      const scrollTo = staticSections.length && staticSections[0].offsetHeight + headerHeight;


      // Define tabs once.
      const tabs = context.querySelectorAll(".field-tabs a");

      // Set class "active" to first tab by default.
      if (tabs[0]) {
        tabs[0].classList.add("active");
      }

      // Add class "active" to clicked tab.
      tabs.forEach(tab => {
        tab.addEventListener("click", () => {
          // Remove class "active" from sibling tabs.
          const tabs_collection = tab.parentNode.children;
          for (const tab of tabs_collection) {
            tab.classList.remove("active")
          }
          // Add class "active" to clicked tab.
          tab.classList.add("active");

          /**
           * Scroll to node block.
           */
          // Set 100vh min height to second section for successfully scrolling,
          // even the height of section less than 100vh.
          staticSections[1].style.minHeight = "100vh";
          setTimeout(() => {
            // Scroll.
            window.scrollTo({
              top: scrollTo,
              behavior: 'smooth'
            });
            // Return min height to prev value.
            staticSections[1].style.minHeight = "auto";
          }, 300);
        });
      });
    }
  }

})(Drupal);
