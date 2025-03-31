<?php declare(strict_types=1);

namespace Magedia\VariantPreview\Command;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProductShortDescriptionImportCommand extends Command
{
    private const OLD_SHORT_DESCRIPTION_TECHNICAL_NAME = 'migration_Live5711_product_attr1';
    private const NEW_SHORT_DESCRIPTION_TECHNICAL_NAME = 'twt_clean_pro_custom_field__product__short_description';

    public function __construct(private readonly EntityRepository $productRepository) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('magedia:product_short_description_import')
            ->setDescription('Import product short description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = $this->productRepository->search(new Criteria(), Context::createDefaultContext());
        $parentData = [];
        $updates = [];

        /** @var ProductEntity $product */
        foreach ($products as $product) {
            $customFields = $product->getCustomFields() ?? [];
            $shortDescription = $customFields[self::OLD_SHORT_DESCRIPTION_TECHNICAL_NAME] ?? null;

            if ($shortDescription && !$product->getParentId()) {
                $parentData[$product->getId()] = $shortDescription;
            }
        }

        /** @var ProductEntity $product */
        foreach ($products as $product) {
            $customFields = $product->getCustomFields() ?? [];
            $shortDescription = $customFields[self::OLD_SHORT_DESCRIPTION_TECHNICAL_NAME] ?? null;

            $takenFromParent = false;
            if (!$shortDescription && $product->getParentId() && isset($parentData[$product->getParentId()])) {
                $shortDescription = $parentData[$product->getParentId()];
                $takenFromParent = true;
            }

            // Fix images
            if ($shortDescription) {
                $shortDescription = $this->fixShortDescriptionImage($shortDescription);
                if (!$takenFromParent) {
                    $customFields[self::OLD_SHORT_DESCRIPTION_TECHNICAL_NAME] = $shortDescription;
                }
            }

            $customFields[self::NEW_SHORT_DESCRIPTION_TECHNICAL_NAME] = $shortDescription;
            $updates[] = [
                'id' => $product->getId(),
                'customFields' => $customFields
            ];
        }

        if (!empty($updates)) {
            $this->productRepository->update($updates, Context::createDefaultContext());
        }

        return Command::SUCCESS;
    }

    private function fixShortDescriptionImage($description): string
    {
        if (str_contains($description, '<img')) {//91
            if (str_contains($description, 'icons-seifen.jpg')) {//43
                $description = '<p><img src="/media/g0/aa/58/1737361889/icons-seifen.jpg" alt="icons-seifen" /></p>';
            } elseif (str_contains($description, 'icons-handcremeI5jws9I5KJWdC.jpg')) {//10
                $description = '<p><img src="/media/e8/d8/80/1737361887/icons-handcremei5jws9i5kjwdc.jpg" alt="icons-handcremeI5jws9I5KJWdC" /></p>';
            } elseif (str_contains($description, 'icons-handcreme-kew.jpg')) {//10
                $description = '<p><img src="/media/e1/c4/a1/1737361900/icons-handcreme-kew.jpg" alt="icons-handcreme-kew" /></p>';
            } elseif (str_contains($description, 'icons-seifen-kew.jpg')) {//15
                $description = '<p><img src="/media/be/be/14/1737361908/icons-seifen-kew.jpg" alt="icons-seifen-kew" /></p>';
            } elseif (str_contains($description, 'icons-gift-box-kew.jpg')) {//13
                $description = '<p><img src="/media/e3/ec/9c/1737361902/icons-gift-box-kew.jpg" alt="icons-gift-box-kew" /></p>';
            }
        }

        return $description;
    }
}
