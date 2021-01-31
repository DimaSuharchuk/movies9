class CustomColorbox {
  constructor(imagePath) {
    this.imagePath = imagePath;
  }

  add() {
    document.body.append(this._buildPopup());
  }

  remove() {
    document.getElementById("image-box-popup-outer")?.remove();
  }

  _buildPopup() {
    const popup = document.createElement("div");
    popup.id = "image-box-popup-outer";
    const popupInner = document.createElement("div");
    popupInner.id = "image-box-popup-inner";
    popup.append(popupInner);
    const image = new Image();
    image.src = this.imagePath;
    popupInner.append(image);

    popup.addEventListener("click", () => this.remove());

    return popup;
  }
}
