<?php declare(strict_types=1);

namespace Magedia\VariantPreview\Subscriber;

use Shopware\Core\Content\Product\Events\ProductSearchResultEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Content\Product\Events\ProductListingResultEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ProductListingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityRepository $productRepository,
        private readonly RouterInterface $router
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductListingResultEvent::class => 'onProductListingResult',
            ProductSearchResultEvent::class => 'onProductSearchResult'
        ];
    }

    public function onProductListingResult(Event $event): void
    {
        $this->addVariantThumbnails($event);
    }

    public function onProductSearchResult(Event $event): void
    {
        $this->addVariantThumbnails($event);
    }

    private function addVariantThumbnails(Event $event): void
    {
        $result = $event->getResult();
        $context = $event->getContext();

        foreach ($result->getEntities() as $product) {
            $parentId = $product->getChildCount() > 1 ? $product->getId() : $product->getParentId();

            if ($parentId) {
                $criteria = new Criteria();
                $criteria
                    ->addFilter(new EqualsFilter('product.parentId', $parentId))
                    ->addAssociation('media');

                $variants = [];
                foreach ($this->productRepository->search($criteria, $context)->getEntities() as $child) {
                    $variants[] = [
                        'url' => $this->router->generate(
                            'frontend.detail.page',
                            ['productId' => $child->getId()],
                            UrlGeneratorInterface::ABSOLUTE_URL
                        ),
                        'image' => $child->getMedia()?->first()?->getMedia()?->getUrl() ?? '',
                        'name' => $child->getTranslation('name') ?? '',
                    ];
                }

                if (count($variants)) {
                    $product->addExtension('variantThumbnails', new ArrayStruct($variants));
                }
            }
        }
    }
}