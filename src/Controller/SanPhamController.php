<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\SanPham;
use App\Entity\Category;
use App\Entity\ProductImage;
use App\Form\SanPhamType;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Annotation\Route;

class SanPhamController extends AbstractController
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    /** Upload any gallery images from the (unmapped) galleryImages field into ProductImage rows. */
    private function handleGallery($form, SanPham $product, FileUploader $fileUploader): void
    {
        $files = $form->get('galleryImages')->getData();
        if (!$files) {
            return;
        }
        $pos = $product->getImages()->count();
        foreach ($files as $file) {
            if (!$file) {
                continue;
            }
            $img = new ProductImage();
            $img->setFilename($fileUploader->upload($file));
            $img->setPosition($pos++);
            $product->addImage($img);
        }
    }

    #[Route('/san/pham', name: 'app_san_pham')]
    public function index(EntityManagerInterface $em, Request $req, FileUploader $fileUploader): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $sp = new SanPham();
        $form = $this->createForm(SanPhamType::class, $sp);
        $form->handleRequest($req);

        if($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $file = $form->get("photo")->getData();
            if (!$file) {
                $this->addFlash('error', 'Vui lòng chọn ảnh chính cho sản phẩm.');
                return $this->render('san_pham/index.html.twig', [
                    'sp_form' => $form->createView(),
                ]);
            }
            $fileName = $fileUploader->upload($file);
            $data->setPhoto($fileName);
            $data->setSlug(SanPham::slugify($data->getName()));
            $this->handleGallery($form, $data, $fileUploader);

            $em->persist($data);
            $em->flush();
            $this->addFlash('success', 'Đã thêm sản phẩm mới.');
            return new RedirectResponse($this->urlGenerator->generate('app_ds_san_pham'));
        }

        return $this->render('san_pham/index.html.twig', [
            'sp_form' => $form->createView(),
        ]);
    }

    #[Route('/san/pham/export.csv', name: 'admin_products_export', methods: ['GET'])]
    public function export(\App\Repository\SanPhamRepository $sanPhamRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $out = "\xEF\xBB\xBF";
        $head = ['ID', 'Tên', 'SKU', 'Thương hiệu', 'Danh mục', 'Giá bán', 'Giá gốc', 'Tồn kho', 'Bảo hành'];
        $out .= implode(',', array_map(fn ($c) => '"' . $c . '"', $head)) . "\r\n";
        foreach ($sanPhamRepository->findAllNewestFirst() as $p) {
            $row = [$p->getId(), $p->getName(), $p->getSku() ?? '', $p->getBrand() ?? '', $p->getCate() ? $p->getCate()->getName() : '', (int) $p->getPrice(), (int) ($p->getPriceOld() ?? 0), $p->getStock(), $p->getWarranty() ?? ''];
            $out .= implode(',', array_map(fn ($c) => '"' . str_replace('"', '""', (string) $c) . '"', $row)) . "\r\n";
        }

        return new Response($out, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="san-pham.csv"']);
    }

    #[Route('/san/pham/ds', name: 'app_ds_san_pham')]
    public function list_sp(\App\Repository\SanPhamRepository $sanPhamRepository, \App\Repository\CategoryRepository $categoryRepository, \Knp\Component\Pager\PaginatorInterface $paginator, Request $req): Response
        {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');

            $q = trim((string) $req->query->get('q', ''));
            $cate = $req->query->getInt('cate') ?: null;
            $stock = (string) $req->query->get('stock', '');
            $sort = (string) $req->query->get('sort', 'newest');

            $qb = $sanPhamRepository->createQueryBuilder('sp')->leftJoin('sp.cate', 'c')->addSelect('c');
            if ($q !== '') {
                $qb->andWhere('sp.name LIKE :q OR sp.sku LIKE :q OR sp.brand LIKE :q')->setParameter('q', '%' . $q . '%');
            }
            if ($cate) {
                $qb->andWhere('c.id = :cate')->setParameter('cate', $cate);
            }
            match ($stock) {
                'out' => $qb->andWhere('sp.stock <= 0'),
                'low' => $qb->andWhere('sp.stock > 0 AND sp.stock <= 5'),
                'in' => $qb->andWhere('sp.stock > 5'),
                'sale' => $qb->andWhere('sp.priceOld IS NOT NULL AND sp.priceOld > sp.price'),
                default => null,
            };
            match ($sort) {
                'price_asc' => $qb->orderBy('sp.price', 'ASC'),
                'price_desc' => $qb->orderBy('sp.price', 'DESC'),
                'stock_asc' => $qb->orderBy('sp.stock', 'ASC'),
                'name' => $qb->orderBy('sp.name', 'ASC'),
                default => $qb->orderBy('sp.id', 'DESC'),
            };

            $pagination = $paginator->paginate($qb->getQuery(), $req->query->getInt('page', 1), 20);

            // Counts for the stock chips (whole catalogue).
            $countWhere = fn (string $where) => (int) $sanPhamRepository->createQueryBuilder('p')
                ->select('COUNT(p.id)')->andWhere($where)->getQuery()->getSingleScalarResult();

            return $this->render('san_pham/list.html.twig', [
                'data' => $pagination,
                'categories' => $categoryRepository->findAllOrdered(),
                'filters' => ['q' => $q, 'cate' => $cate, 'stock' => $stock, 'sort' => $sort],
                'counts' => [
                    '' => $sanPhamRepository->count([]),
                    'in' => $countWhere('p.stock > 5'),
                    'low' => $countWhere('p.stock > 0 AND p.stock <= 5'),
                    'out' => $countWhere('p.stock <= 0'),
                    'sale' => $countWhere('p.priceOld IS NOT NULL AND p.priceOld > p.price'),
                ],
            ]);
        }

        #[Route('/san/pham/{id}', name: 'app_edit_san_pham')]
        public function edit(EntityManagerInterface $em, int $id, Request $req, FileUploader $fileUploader): Response
            {
                $this->denyAccessUnlessGranted('ROLE_ADMIN');
                $sp = $em->find(SanPham::class, $id);
                $form = $this->createForm(SanPhamType::class, $sp);
                $form->handleRequest($req);
                
                if($form->isSubmitted() && $form->isValid()) {
                    $data = $form->getData();
                    
                    $file = $form->get("photo")->getData();
                    if($file) {
                        $fileName = $fileUploader->upload($file);
                        $data->setPhoto($fileName);
                    }
                    $sp->setSlug(SanPham::slugify($sp->getName()));
                    $this->handleGallery($form, $sp, $fileUploader);
                    $em->flush();
                    $this->addFlash('success', 'Đã lưu thay đổi sản phẩm.');
                    return new RedirectResponse($this->urlGenerator->generate('app_ds_san_pham'));
                }
                return $this->render('san_pham/index.html.twig', [
                    'sp_form' => $form->createView(),
                    'product' => $sp,
                ]);
            }

        #[Route('/san/pham/{id}/delete', name: 'app_delete_san_pham', methods: ['POST'])]
        public function delete(EntityManagerInterface $em, int $id, Request $req): Response
            {
                $this->denyAccessUnlessGranted('ROLE_ADMIN');
                if (!$this->isCsrfTokenValid('delete_product_' . $id, $req->request->get('_token'))) {
                    return new RedirectResponse($this->urlGenerator->generate('app_ds_san_pham'));
                }
                $sp = $em->find(SanPham::class, $id);
                if ($sp) {
                    try {
                        $em->remove($sp);
                        $em->flush();
                        $this->addFlash('success', 'Đã xóa sản phẩm.');
                    } catch (\Throwable $e) {
                        $this->addFlash('error', 'Không thể xóa: sản phẩm đã có trong đơn hàng.');
                    }
                }

                return new RedirectResponse($this->urlGenerator->generate('app_ds_san_pham'));
            }

        #[Route('/cate/{id}', name: 'app_ds_san_pham_in_category')]
        public function listSPinCate(EntityManagerInterface $em, int $id, Request $req, \Knp\Component\Pager\PaginatorInterface $paginator): Response
            {
                $this->denyAccessUnlessGranted('ROLE_ADMIN');
                $cate = $em->find(Category::class, $id);
                if (!$cate) {
                    throw $this->createNotFoundException('Danh mục không tồn tại.');
                }
                // Legacy URL: the product list now filters by category itself.
                return $this->redirectToRoute('app_ds_san_pham', ['cate' => $cate->getId()]);
            }
            
}
