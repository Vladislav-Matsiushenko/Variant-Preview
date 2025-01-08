const { PluginBaseClass } = window;

export default class MagediaVariantPreview extends PluginBaseClass {
    init() {
        this.initImageUpdate()
        this.initThumbnailsScroll()
    }

    initImageUpdate() {
        const mainImage = this.el.closest('.card-body')?.querySelector('.product-image-wrapper .product-image.is-standard'),
            thumbnails = this.el.querySelectorAll('.product-variants .variant-link');

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
    }

    initThumbnailsScroll() {
        const productVariants = this.el.querySelector('.product-variants'),
            leftArrow = this.el.querySelector('.variant-arrow.left'),
            rightArrow = this.el.querySelector('.variant-arrow.right'),
            thumbnailWidth = productVariants.querySelectorAll('.variant-link')[0]?.offsetWidth;

        if (!productVariants || !leftArrow || !rightArrow || !thumbnailWidth) {
            return;
        }

        const scrollStep = productVariants.clientWidth;

        const updateArrows = () => {
            const scrollLeft = productVariants.scrollLeft;

            leftArrow.style.display = scrollLeft > 0 ? 'block' : 'none';
            rightArrow.style.display = scrollLeft < productVariants.scrollWidth - scrollStep ? 'block' : 'none';
        };

        leftArrow.addEventListener('click', () => {
            productVariants.scrollBy({
                left: -scrollStep,
                behavior: 'smooth'
            });
        });

        rightArrow.addEventListener('click', () => {
            productVariants.scrollBy({
                left: scrollStep,
                behavior: 'smooth'
            });
        });

        productVariants.addEventListener('scroll', updateArrows);
        updateArrows();
    }
}