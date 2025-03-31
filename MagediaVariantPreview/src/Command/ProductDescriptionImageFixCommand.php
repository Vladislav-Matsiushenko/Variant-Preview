<?php declare(strict_types=1);

namespace Magedia\VariantPreview\Command;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProductDescriptionImageFixCommand extends Command
{
    private const OLD_SHORT_DESCRIPTION_TECHNICAL_NAME = 'migration_Live5711_product_attr1';

    public function __construct(private readonly EntityRepository $productRepository) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('magedia:product_description_image_fix')
            ->setDescription('Fix product description image');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = $this->productRepository->search(new Criteria(), Context::createDefaultContext());
        $totalMatches = 0;
        $files = [];

        /** @var ProductEntity $product */
        foreach ($products as $product) {
            $customFields = $product->getCustomFields() ?? [];
            $shortDescription = $customFields[self::OLD_SHORT_DESCRIPTION_TECHNICAL_NAME] ?? null;
            if ($shortDescription && str_contains($shortDescription, '<img')) {
                $output->writeln($product->getProductNumber());
                $output->writeln($shortDescription);
                $output->writeln('');
                $totalMatches++;
            }
//            if ($description) {
//                $pattern = '/<img[^>]*src=["\']%7Bmedia%20path=["\']([^"\']+)["\'][^>]*\/>/i';
//                preg_match_all($pattern, $description, $matches);
//                $totalMatches += count($matches[1]);
//                if ($matches[0]) {
//                    var_dump($matches);
//                }
//
//
//                foreach ($matches[1] as $srcPath) {
//                    $filePath = str_replace('media/image/', '', $srcPath);
//                    $output->writeln($filePath);
//                    $files[] = $filePath;
//                }
//            }
        }

        $output->writeln((string)$totalMatches);
        var_dump(array_unique($files));

        return Command::SUCCESS;
    }
}
