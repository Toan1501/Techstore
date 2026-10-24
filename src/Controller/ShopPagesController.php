<?php

namespace App\Controller;

use App\Entity\SanPham;
use App\Repository\CategoryRepository;
use App\Repository\CouponRepository;
use App\Repository\ReviewRepository;
use App\Repository\SanPhamRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Storefront discovery pages: promotions, brands, showrooms, PC builder and
 * need-based SEO landing pages.
 */
class ShopPagesController extends AbstractController
{
    /** Need-based landing pages: slug => category name + name keywords (OR) + SEO copy. */
    public const LANDING = [
        'laptop-gaming' => [
            'category' => 'Laptop', 'q' => ['Gaming', 'RTX', 'GTX', 'ROG', 'Katana', 'Nitro', 'TUF', 'G5'],
            'title' => 'Laptop Gaming chính hãng, giá tốt',
            'meta' => 'Laptop gaming Asus ROG, MSI Katana, Acer Nitro, Lenovo IdeaPad Gaming… card RTX/GTX, màn hình 144Hz, trả góp 0% tại Toàn Store.',
            'intro' => 'Tuyển chọn laptop gaming cấu hình mạnh với card đồ họa rời RTX/GTX, màn hình tần số quét cao và tản nhiệt tốt — phù hợp chơi game AAA lẫn đồ họa, dựng phim.',
        ],
        'laptop-van-phong' => [
            'category' => 'Laptop', 'q' => ['Vivobook', 'Inspiron', 'Pavilion', 'Modern', 'ThinkPad', 'MacBook', 'Aspire'],
            'title' => 'Laptop văn phòng mỏng nhẹ, pin lâu',
            'meta' => 'Laptop văn phòng Dell Inspiron, HP Pavilion, Asus Vivobook, Lenovo ThinkPad, MacBook Air… mỏng nhẹ, pin bền, giá tốt tại Toàn Store.',
            'intro' => 'Laptop mỏng nhẹ cho học tập và làm việc: hiệu năng ổn định, thời lượng pin dài, bàn phím thoải mái — chọn theo ngân sách từ 10 đến hơn 25 triệu.',
        ],
        'pc-gaming' => [
            'category' => 'Desktop PC', 'q' => ['Gaming', 'RTX', 'GVN', 'Streaming', 'Đồ họa', 'ITX'],
            'title' => 'PC Gaming lắp sẵn, cấu hình mạnh',
            'meta' => 'PC gaming lắp ráp sẵn với card RTX 4060 – 4080 Super, CPU Intel i5/i7/i9 và Ryzen, bảo hành 36 tháng, trả góp 0% tại Toàn Store.',
            'intro' => 'Bộ PC gaming được lắp ráp và kiểm thử sẵn, cân mọi tựa game từ Full HD tới 2K/4K. Muốn tự chọn linh kiện? Dùng công cụ xây dựng cấu hình.',
        ],
        'pc-van-phong' => [
            'category' => 'Desktop PC', 'q' => ['Văn phòng', 'Học tập'],
            'title' => 'PC văn phòng, học tập tiết kiệm',
            'meta' => 'Máy tính để bàn cho văn phòng và học tập: Core i3/i5, Ryzen 3, SSD nhanh, giá từ 8 triệu, bảo hành 24 tháng tại Toàn Store.',
            'intro' => 'Cấu hình tối ưu cho tác vụ văn phòng, học online và giải trí nhẹ: khởi động nhanh nhờ SSD, vận hành êm, tiết kiệm điện.',
        ],
        'man-hinh-gaming' => [
            'category' => 'Monitor', 'q' => ['Hz', 'Gaming', 'UltraGear', 'Odyssey', 'TUF'],
            'title' => 'Màn hình Gaming 144Hz – 280Hz',
            'meta' => 'Màn hình gaming LG UltraGear, Samsung Odyssey, Asus TUF, Gigabyte… tần số quét 144Hz đến 280Hz, phản hồi 1ms tại Toàn Store.',
            'intro' => 'Màn hình tần số quét cao, phản hồi 1ms cho FPS/MOBA mượt mà; có lựa chọn 2K, cong và ultrawide cho trải nghiệm đắm chìm.',
        ],
    ];

    public function __construct(
        private SanPhamRepository $products,
        private CategoryRepository $categories,
        private ReviewRepository $reviews,
    ) {
    }

    #[Route('/khuyen-mai', name: 'shop_promotions')]
    public function promotions(CouponRepository $coupons, PaginatorInterface $paginator, Request $request): Response
    {
        $sort = $request->query->get('sort', 'discount');
        $data = $paginator->paginate(
            $this->products->queryFiltered(['discount_only' => true, 'sort' => $sort]),
            $request->query->getInt('page', 1),
            12
        );
        $active = array_values(array_filter($coupons->findAll(), fn ($c) => $c->isValidNow()));

        return $this->render('shop/promotions.html.twig', [
            'data' => $data, 'ratings' => $this->ratingsFor($data), 'coupons' => $active, 'sort' => $sort,
        ]);
    }

    #[Route('/thuong-hieu', name: 'shop_brands')]
    public function brands(): Response
    {
        $brands = [];
        foreach ($this->products->brandCounts() as $name => $count) {
            $brands[] = ['name' => $name, 'slug' => SanPham::slugify($name), 'count' => $count];
        }

        return $this->render('shop/brands.html.twig', ['brands' => $brands]);
    }

    #[Route('/thuong-hieu/{slug}', name: 'shop_brand')]
    public function brand(string $slug, PaginatorInterface $paginator, Request $request): Response
    {
        $brand = null;
        foreach (array_keys($this->products->brandCounts()) as $name) {
            if (SanPham::slugify($name) === $slug) { $brand = $name; break; }
        }
        if ($brand === null) {
            throw $this->createNotFoundException('Thương hiệu không tồn tại.');
        }
        $sort = $request->query->get('sort', 'newest');
        $data = $paginator->paginate(
            $this->products->queryFiltered(['brand' => $brand, 'sort' => $sort]),
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('homepage/sanpham.html.twig', [
            'data' => $data, 'ratings' => $this->ratingsFor($data), 'sort' => $sort,
            'heading' => 'Sản phẩm ' . $brand,
            'intro' => 'Toàn bộ sản phẩm ' . $brand . ' chính hãng đang bán tại Toàn Store, bảo hành theo chính sách của hãng.',
        ]);
    }

    #[Route('/he-thong-cua-hang', name: 'shop_stores')]
    public function stores(): Response
    {
        $stores = [
            ['name' => 'Toàn Store Quận 1 (Trụ sở)', 'address' => '123 Lê Lợi, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh', 'phone' => '1900 5301', 'hours' => 'Mo-Su 08:00-21:00'],
            ['name' => 'Toàn Store Thủ Đức', 'address' => '45 Võ Văn Ngân, Phường Linh Chiểu, TP. Thủ Đức, TP. Hồ Chí Minh', 'phone' => '1900 5301', 'hours' => 'Mo-Su 08:30-21:00'],
            ['name' => 'Toàn Store Hà Nội', 'address' => '68 Thái Hà, Phường Trung Liệt, Quận Đống Đa, Hà Nội', 'phone' => '1900 5301', 'hours' => 'Mo-Su 08:30-21:00'],
        ];

        return $this->render('shop/stores.html.twig', ['stores' => $stores]);
    }

    #[Route('/xay-dung-cau-hinh', name: 'shop_builder')]
    public function builder(): Response
    {
        $defs = [
            ['key' => 'pc', 'cat' => 'Desktop PC', 'label' => 'Máy tính', 'icon' => 'pc-display', 'required' => true, 'hint' => 'Bộ máy nền tảng: CPU, mainboard, RAM, SSD và nguồn đã lắp sẵn.'],
            ['key' => 'vga', 'cat' => 'Graphics Card', 'label' => 'Card đồ họa nâng cấp', 'icon' => 'gpu-card', 'required' => false, 'hint' => 'Chọn nếu muốn thay card mạnh hơn cấu hình có sẵn.'],
            ['key' => 'ram', 'cat' => 'RAM', 'label' => 'RAM nâng cấp', 'icon' => 'memory', 'required' => false, 'hint' => 'Thêm RAM để đa nhiệm, livestream mượt hơn.'],
            ['key' => 'monitor', 'cat' => 'Monitor', 'label' => 'Màn hình', 'icon' => 'display', 'required' => false, 'hint' => ''],
            ['key' => 'mouse', 'cat' => 'Mouse', 'label' => 'Chuột', 'icon' => 'mouse2', 'required' => false, 'hint' => ''],
        ];
        $slots = [];
        foreach ($defs as $d) {
            $cat = $this->categories->findOneBy(['name' => $d['cat']]);
            if (!$cat) { continue; }
            $list = array_filter($this->products->findBy(['cate' => $cat], ['price' => 'ASC']), fn (SanPham $p) => $p->isInStock());
            $d['products'] = array_values($list);
            $slots[] = $d;
        }

        return $this->render('shop/builder.html.twig', ['slots' => $slots]);
    }

    #[Route('/{slug}', name: 'shop_landing', requirements: ['slug' => 'laptop-gaming|laptop-van-phong|pc-gaming|pc-van-phong|man-hinh-gaming'])]
    public function landing(string $slug, PaginatorInterface $paginator, Request $request): Response
    {
        $cfg = self::LANDING[$slug];
        $cat = $this->categories->findOneBy(['name' => $cfg['category']]);
        $sort = $request->query->get('sort', 'newest');
        $filters = ['q' => $cfg['q'], 'sort' => $sort];
        if ($cat) { $filters['category'] = $cat; }
        $data = $paginator->paginate($this->products->queryFiltered($filters), $request->query->getInt('page', 1), 12);

        return $this->render('homepage/sanpham.html.twig', [
            'data' => $data, 'ratings' => $this->ratingsFor($data), 'sort' => $sort,
            'heading' => $cfg['title'], 'intro' => $cfg['intro'], 'meta_description' => $cfg['meta'],
        ]);
    }

    private function ratingsFor(iterable $products): array
    {
        $ids = [];
        foreach ($products as $p) { $ids[] = $p->getId(); }

        return $this->reviews->getRatingsMap($ids);
    }
}
