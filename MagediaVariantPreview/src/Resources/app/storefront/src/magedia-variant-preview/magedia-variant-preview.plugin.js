const { PluginBaseClass } = window;

export default class MagediaVariantPreview extends PluginBaseClass {
    init() {
        const productContainers = document.querySelectorAll('.product-box');

        productContainers.forEach(productContainer => {
            const mainImage = productContainer.querySelector('.product-image.is-standard');
            const thumbnails = productContainer.querySelectorAll('.product-variants .variant-link');

            if (!mainImage || thumbnails.length === 0) {
                return;
            }

            if (!mainImage.hasAttribute('data-original')) {
                mainImage.setAttribute('data-original', mainImage.src);
            }

            thumbnails.forEach(thumbnail => {
                thumbnail.addEventListener('mouseover', () => {
                    const newImage = thumbnail.getAttribute('data-image');
                    if (newImage) {
                        mainImage.srcset = newImage;
                    }
                });

                thumbnail.addEventListener('mouseout', () => {
                    const originalImage = mainImage.getAttribute('data-original');
                    if (originalImage) {
                        mainImage.srcset = originalImage;
                    }
                });
            });
        });
    }
}