<?php declare(strict_types=1);

namespace Magedia\VariantPreview\Command;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProductStorefrontImageImportCommand extends Command
{
    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly EntityRepository $productRepository,
        private readonly EntityRepository $productConfiguratorSettingRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('magedia:product_storefront_image_import')
            ->setDescription('Import product storefront image');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $propertyGroupId = $this->systemConfigService->get('MagediaVariantPreview.config.propertyGroupIds');
        if (!$propertyGroupId) {
            return Command::SUCCESS;
        }

        $output->writeln($propertyGroupId);
        $updates = [];

        $criteria = new Criteria();
        $criteria
            ->addAssociation('configuratorSettings.option')
            ->addAssociation('configuratorSettings.option.group');
        $products = $this->productRepository->search($criteria, Context::createDefaultContext());

        /** @var ProductEntity $product */
        foreach ($products as $product) {
            if (!$product->getParentId()) {
                $parentId = $product->getId();

                $configuratorSettings = $product->getConfiguratorSettings();
                foreach ($configuratorSettings as $setting) {

                    $option = $setting->getOption();
                    if (in_array($option->getGroup()?->getId(), $propertyGroupId)) {
                        $optionName = $option->getTranslation('name');

                        $criteria = new Criteria();
                        $criteria
                            ->addFilter(new EqualsFilter('product.parentId', $parentId))
                            ->addAssociation('options.group')
                            ->addAssociation('media');

                        foreach ($this->productRepository->search($criteria, Context::createDefaultContext())->getEntities() as $child) {
                            foreach ($child->getOptions() as $option) {
                                if (in_array($option->getGroup()?->getId(), $propertyGroupId)) {
                                    if ($optionName == $option->getTranslation('name')) {
                                        $imageId = $child->getMedia()?->first()?->getMedia()?->getId();
                                        if ($imageId) {

                                            if ($product->getProductNumber() == '150-1-CRM') {

                                                $updates[] = [
                                                    'id' => $setting->getId(),
                                                    'mediaId' => $imageId,
                                                ];

                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($updates) {
            $this->productConfiguratorSettingRepository->update($updates, Context::createDefaultContext());
            $output->writeln('Updated');
        }

        return Command::SUCCESS;
    }
}
