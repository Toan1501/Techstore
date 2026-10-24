<?php

namespace App\Command;

use App\Entity\ProductImage;
use App\Entity\SanPham;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Point existing products at their generated photos (p-<sku>.webp, gallery p-<sku>-N.webp)
 * without reseeding, so live data (users, orders) is kept.
 */
#[AsCommand(name: 'app:sync-photos', description: 'Use generated per-SKU photos for existing products')]
class SyncPhotosCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%/public/uploads/sanpham')]
        private string $uploadDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $main = 0;
        $gallery = 0;

        foreach ($this->em->getRepository(SanPham::class)->findAll() as $p) {
            if (!$p->getSku()) {
                continue;
            }
            $base = 'p-' . strtolower($p->getSku());
            if (!is_file($this->uploadDir . '/' . $base . '.webp')) {
                continue;
            }

            if ($p->getPhoto() !== $base . '.webp') {
                $p->setPhoto($base . '.webp');
                $main++;
            }

            // Replace the gallery with the generated extra angles (drops old third-party images).
            $extra = [];
            for ($n = 2; $n <= 6; $n++) {
                if (is_file($this->uploadDir . '/' . $base . '-' . $n . '.webp')) {
                    $extra[] = $base . '-' . $n . '.webp';
                }
            }
            $current = array_map(static fn (ProductImage $i) => $i->getFilename(), $p->getImages()->toArray());
            if ($current !== $extra) {
                foreach ($p->getImages()->toArray() as $img) {
                    $p->removeImage($img);
                    $this->em->remove($img);
                }
                foreach ($extra as $i => $file) {
                    $p->addImage((new ProductImage())->setFilename($file)->setPosition($i));
                }
                $gallery++;
            }
        }

        $this->em->flush();
        $io->success(sprintf('Updated %d main photos and %d galleries.', $main, $gallery));

        return Command::SUCCESS;
    }
}
