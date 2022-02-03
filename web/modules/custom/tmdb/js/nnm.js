(D => {
  D.behaviors.loadNnmLinks = {
    attach: context => {
      context.querySelectorAll("a[data-topic-id]").forEach(link => {
        link.addEventListener("click", evt => {
          evt.preventDefault();

          const href = link.getAttribute("href");

          fetch(href)
            .then(response => response.json())
            .then(data => {
              const fileLink = data.torrent_link || null,
                magnetLink = data.magnet_link || null;

              const replace = document.createElement("div");
              replace.classList.add("nnm-links-container");

              if (fileLink) {
                const file = document.createElement("a");
                file.href = fileLink;
                file.classList.add("nnm-torrent-link", "file");
                file.title = Drupal.t("Download torrent file", {}, {context: "nnm"});
                replace.appendChild(file);
              }
              if (magnetLink) {
                const magnet = document.createElement("a");
                magnet.href = magnetLink;
                magnet.classList.add("nnm-torrent-link", "magnet");
                magnet.title = Drupal.t("Magnetize", {}, {context: "nnm"});
                replace.appendChild(magnet);
              }

              if (!replace.hasChildNodes()) {
                replace.textContent = "-";
              }

              link.replaceWith(replace);
            });
        });
      });
    }
  };

})(Drupal);
