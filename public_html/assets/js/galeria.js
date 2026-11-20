const productImage = document.querySelector('#product-image');
const imagePlaceholder = document.querySelector('#image-placeholder');
const photoButtons = document.querySelectorAll('.thumbs button');

function updateImageVisibility() {
  const loaded = productImage.complete && productImage.naturalWidth > 0;
  productImage.hidden = !loaded;
  imagePlaceholder.hidden = loaded;
}

productImage.addEventListener('load', updateImageVisibility);
productImage.addEventListener('error', updateImageVisibility);
updateImageVisibility();

for (const button of photoButtons) {
  button.addEventListener('click', () => {
    if (button.getAttribute('aria-pressed') === 'true') return;

    document.querySelector('.thumbs [aria-pressed="true"]').setAttribute('aria-pressed', 'false');
    button.setAttribute('aria-pressed', 'true');
    productImage.hidden = true;
    imagePlaceholder.hidden = false;
    imagePlaceholder.textContent = button.dataset.label;
    productImage.alt = button.dataset.label;
    productImage.src = 'assets/img/' + button.dataset.file;
    updateImageVisibility();
  });
}
