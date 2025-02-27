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

            if (!$shortDescription && $product->getParentId() && isset($parentData[$product->getParentId()])) {
                $shortDescription = $parentData[$product->getParentId()];
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
}
