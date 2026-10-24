<?php

namespace App\Controller;

use App\Catalog\CatalogMeta;
use App\Catalog\ListingFilters;
use App\Entity\Category;
use App\Entity\SanPham;
use App\Repository\CategoryRepository;
use App\Repository\ReviewRepository;
use App\Repository\SanPhamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    public function __construct(
        private SanPhamRepository $sanPhamRepository,
        private CategoryRepository $categoryRepository,
        private ReviewRepository $reviewRepository,
    ) {
    }

    /** Build a productId => {average, count} map for a set of products (single query). */
    private function ratingsFor(iterable $products): array
    {
        $this->sanPhamRepository->preloadImages($products);
        $ids = [];
        foreach ($products as $p) {
            $ids[] = $p->getId();
        }

        return $this->reviewRepository->getRatingsMap($ids);
    }

    #[Route('/feature', name: 'feature_product')]
    public function viewdetail(PaginatorInterface $paginator, Request $request): Response
    {
        $filters = ListingFilters::fromRequest($request);
        $pagination = $paginator->paginate(
            $this->sanPhamRepository->queryFiltered($filters),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('homepage/sanpham.html.twig', [
            'data' => $pagination,
            'ratings' => $this->ratingsFor($pagination),
            'heading' => 'Tất cả sản phẩm',
            'sort' => $filters['sort'],
            'filters' => $filters,
            'facets' => ListingFilters::facets($this->sanPhamRepository),
        ]);
    }

    #[Route('/category', name: 'app_category')]
    public function viewCate(): Response
    {
        return $this->render('homepage/category.html.twig', [
            'category' => $this->categoryRepository->findAllOrdered(),
        ]);
    }

    #[Route('/san-pham/{slug}-{id}', name: 'app_product', requirements: ['id' => '\d+', 'slug' => '[a-z0-9\-]+'])]
    #[Route('/feature/{id}', name: 'app_detailsp', requirements: ['id' => '\d+'])]
    public function detail(SanPham $product, Request $request, \App\Repository\QuestionRepository $questionRepo): Response
    {
        // Canonical SEO URL: redirect legacy /feature/{id} or wrong slug to /san-pham/{slug}-{id}
        $slug = $product->getSlug();
        if ($request->attributes->get('_route') !== 'app_product' || $request->attributes->get('slug') !== $slug) {
            return $this->redirectToRoute('app_product', ['slug' => $slug, 'id' => $product->getId()], 301);
        }

        // Track recently viewed (session), most recent first, capped.
        $session = $request->getSession();
        $viewed = array_values(array_diff($session->get('recently_viewed', []), [$product->getId()]));
        array_unshift($viewed, $product->getId());
        $viewed = array_slice($viewed, 0, 12);
        $session->set('recently_viewed', $viewed);

        $rvIds = array_slice(array_values(array_diff($viewed, [$product->getId()])), 0, 6);
        $recentlyViewed = $rvIds ? $this->sanPhamRepository->findBy(['id' => $rvIds]) : [];

        return $this->render('homepage/detail.html.twig', [
            'data' => $product,
            'reviews' => $this->reviewRepository->findByProduct($product),
            'rating' => $this->reviewRepository->getRatingSummary($product),
            'related' => $this->sanPhamRepository->findRelated($product, 10),
            'recently_viewed' => $recentlyViewed,
            'questions' => $questionRepo->findByProduct($product),
        ]);
    }

    #[Route('/', name: 'homepage')]
    public function cate(\App\Repository\BannerRepository $bannerRepository, \App\Repository\NewsRepository $newsRepository): Response
    {
        $deals = $this->sanPhamRepository->findTopDeals(12);
        $categories = CatalogMeta::sort($this->categoryRepository->findAllOrdered());

        // One carousel per category: newest products + its top brands as quick-filter chips.
        $sections = [];
        $shown = $deals;
        foreach ($categories as $c) {
            $prods = $this->sanPhamRepository->findBy(['cate' => $c], ['id' => 'DESC'], 10);
            if ($prods) {
                $sections[] = [
                    'category' => $c,
                    'label' => CatalogMeta::label($c->getName()),
                    'icon' => CatalogMeta::icon($c->getName()),
                    'brands' => array_slice(array_keys($this->sanPhamRepository->brandCountsFor($c)), 0, 5),
                    'products' => $prods,
                ];
                $shown = array_merge($shown, $prods);
            }
        }

        // "Gợi ý hôm nay": a daily-rotating pick across the whole catalogue.
        $all = $this->sanPhamRepository->findAll();
        mt_srand((int) date('Ymd'));
        shuffle($all);
        mt_srand();
        $picks = array_slice($all, 0, 10);
        $shown = array_merge($shown, $picks);

        return $this->render('homepage/index.html.twig', [
            'category' => $categories,
            'deals' => $deals,
            'sections' => $sections,
            'picks' => $picks,
            'ratings' => $this->ratingsFor($shown),
            'banners' => $bannerRepository->findActiveOrdered(),
            'news' => array_slice($newsRepository->findAllOrdered(), 0, 4),
        ]);
    }

    #[Route('/category/{id}', name: 'app_productofcate', requirements: ['id' => '\d+'])]
    public function viewCategoryProducts(Category $category, PaginatorInterface $paginator, Request $request): Response
    {
        $filters = ListingFilters::fromRequest($request);
        $pagination = $paginator->paginate(
            $this->sanPhamRepository->queryFiltered(['category' => $category] + $filters),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('homepage/sanpham.html.twig', [
            'data' => $pagination,
            'ratings' => $this->ratingsFor($pagination),
            'heading' => CatalogMeta::label($category->getName()),
            'category' => $category,
            'sort' => $filters['sort'],
            'filters' => $filters,
            'facets' => ListingFilters::facets($this->sanPhamRepository, $category),
        ]);
    }

    #[Route('/profile', name: 'profile')]
    public function profile(): Response
    {
        return $this->render('homepage/profile.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/profile/edit', name: 'edit_profile')]
    public function editProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('edit_profile', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Phiên làm việc không hợp lệ, vui lòng thử lại.');
                return $this->redirectToRoute('edit_profile');
            }

            $firstName = trim((string) $request->request->get('first_name'));
            $lastName = trim((string) $request->request->get('last_name'));
            $address = trim((string) $request->request->get('address'));
            $phone = trim((string) $request->request->get('phone_number'));

            if ($firstName === '' || $lastName === '' || $address === '') {
                $this->addFlash('error', 'Vui lòng điền đầy đủ họ, tên và địa chỉ.');
                return $this->redirectToRoute('edit_profile');
            }
            if (!preg_match('/^[0-9]{9,12}$/', $phone)) {
                $this->addFlash('error', 'Số điện thoại phải gồm 9–12 chữ số.');
                return $this->redirectToRoute('edit_profile');
            }

            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setAddress($address);
            $user->setPhonenumber($phone);
            $entityManager->flush();

            $this->addFlash('success', 'Đã cập nhật thông tin tài khoản.');

            return $this->redirectToRoute('profile');
        }

        return $this->render('homepage/settings.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/search', name: 'search_products')]
    public function search(Request $request, PaginatorInterface $paginator): Response
    {
        $keyword = trim((string) $request->query->get('keyword'));
        $filters = ListingFilters::fromRequest($request);
        $pagination = $paginator->paginate(
            $this->sanPhamRepository->queryFiltered(($keyword !== '' ? ['q' => $keyword] : []) + $filters),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('homepage/sanpham.html.twig', [
            'data' => $pagination,
            'ratings' => $this->ratingsFor($pagination),
            'heading' => $keyword !== '' ? 'Kết quả cho "' . $keyword . '"' : 'Tìm kiếm',
            'keyword' => $keyword,
            'sort' => $filters['sort'],
            'filters' => $filters,
            'facets' => ListingFilters::facets($this->sanPhamRepository),
        ]);
    }

    #[Route('/search-api', name: 'search_api', methods: ['GET'])]
    public function apiSearch(Request $request): JsonResponse
    {
        $keyword = $request->query->get('keyword');
        $products = $this->sanPhamRepository->findByKeywordWithLimit($keyword);

        $results = [];
        foreach ($products as $product) {
            $results[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'photo' => $product->getPhoto(),
                'price' => $product->getPrice(),
                'priceOld' => $product->hasDiscount() ? $product->getPriceOld() : null,
                'url' => $this->generateUrl('app_product', ['slug' => $product->getSlug(), 'id' => $product->getId()]),
            ];
        }

        return new JsonResponse($results);
    }
}
