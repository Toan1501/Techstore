<?php

namespace App\Controller;

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
        $ids = [];
        foreach ($products as $p) {
            $ids[] = $p->getId();
        }

        return $this->reviewRepository->getRatingsMap($ids);
    }

    #[Route('/feature', name: 'feature_product')]
    public function viewdetail(PaginatorInterface $paginator, Request $request): Response
    {
        $pagination = $paginator->paginate(
            $this->sanPhamRepository->queryFiltered(['sort' => 'newest']),
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('homepage/sanpham.html.twig', [
            'data' => $pagination,
            'ratings' => $this->ratingsFor($pagination),
            'heading' => 'All products',
        ]);
    }

    #[Route('/category', name: 'app_category')]
    public function viewCate(): Response
    {
        return $this->render('homepage/category.html.twig', [
            'category' => $this->categoryRepository->findAllOrdered(),
        ]);
    }

    #[Route('/feature/{id}', name: 'app_detailsp', requirements: ['id' => '\d+'])]
    public function detail(SanPham $product): Response
    {
        return $this->render('homepage/detail.html.twig', [
            'data' => $product,
            'reviews' => $this->reviewRepository->findByProduct($product),
            'rating' => $this->reviewRepository->getRatingSummary($product),
            'related' => $this->sanPhamRepository->findRelated($product, 4),
        ]);
    }

    #[Route('/', name: 'homepage')]
    public function cate(PaginatorInterface $paginator, Request $request, \App\Repository\BannerRepository $bannerRepository): Response
    {
        $sort = $request->query->get('sort', 'newest');
        if (!in_array($sort, SanPhamRepository::SORTS, true)) {
            $sort = 'newest';
        }

        $categoryId = $request->query->getInt('category') ?: null;
        $filters = [
            'sort' => $sort,
            'category' => $categoryId,
            'min_price' => $request->query->get('min_price'),
            'max_price' => $request->query->get('max_price'),
            'in_stock' => $request->query->getBoolean('in_stock'),
        ];

        $pagination = $paginator->paginate(
            $this->sanPhamRepository->queryFiltered($filters),
            $request->query->getInt('page', 1),
            8
        );

        $deals = $this->sanPhamRepository->findDeals(6);
        $categories = $this->categoryRepository->findAllOrdered();

        // Per-category product sections (a few products each) for the homepage.
        $sections = [];
        $sectionProducts = [];
        foreach ($categories as $c) {
            $prods = $this->sanPhamRepository->findBy(['cate' => $c], ['id' => 'DESC'], 6);
            if ($prods) {
                $sections[] = ['category' => $c, 'products' => $prods];
                $sectionProducts = array_merge($sectionProducts, $prods);
            }
        }

        return $this->render('homepage/index.html.twig', [
            'category' => $categories,
            'data' => $pagination,
            'sort' => $sort,
            'ratings' => $this->ratingsFor($pagination),
            'deals' => $deals,
            'deals_ratings' => $this->ratingsFor($deals),
            'sections' => $sections,
            'section_ratings' => $this->ratingsFor($sectionProducts),
            'banners' => $bannerRepository->findActiveOrdered(),
            'price_bounds' => $this->sanPhamRepository->priceBounds(),
            'filters' => $filters,
        ]);
    }

    #[Route('/category/{id}', name: 'app_productofcate', requirements: ['id' => '\d+'])]
    public function viewCategoryProducts(Category $category, PaginatorInterface $paginator, Request $request): Response
    {
        $pagination = $paginator->paginate(
            $this->sanPhamRepository->queryFiltered(['category' => $category, 'sort' => 'newest']),
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('homepage/sanpham.html.twig', [
            'data' => $pagination,
            'ratings' => $this->ratingsFor($pagination),
            'heading' => 'Category: ' . $category->getName(),
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
            $user->setFirstName($request->request->get('first_name'));
            $user->setLastName($request->request->get('last_name'));
            $user->setAddress($request->request->get('address'));
            $user->setPhonenumber($request->request->get('phone_number'));
            $entityManager->flush();

            $this->addFlash('success', 'Your profile has been updated.');

            return $this->redirectToRoute('profile');
        }

        return $this->render('homepage/settings.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/search', name: 'search_products')]
    public function search(Request $request, PaginatorInterface $paginator): Response
    {
        $keyword = $request->query->get('keyword');
        $pagination = $paginator->paginate(
            $this->sanPhamRepository->queryByKeyword($keyword),
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('homepage/sanpham.html.twig', [
            'data' => $pagination,
            'ratings' => $this->ratingsFor($pagination),
            'heading' => 'Search: "' . $keyword . '"',
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
            ];
        }

        return new JsonResponse($results);
    }
}
