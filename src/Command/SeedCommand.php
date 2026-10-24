<?php

namespace App\Command;

use App\Entity\Banner;
use App\Entity\Category;
use App\Entity\Coupon;
use App\Entity\News;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\ProductImage;
use App\Entity\Review;
use App\Entity\SanPham;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:seed', description: 'Wipe and seed the database with demo data.')]
class SeedCommand extends Command
{
    /**
     * AI-generated product shots (ChatGPT, white catalog background) for the 50
     * extra products, matched by SKU to a gaming or office/plain variant.
     */
    private const AI_PHOTOS = [
        // Laptop
        'LAP-LEN-IPG3' => 'ai-laptop-1.webp', 'LAP-ASUS-A15' => 'ai-laptop-1.webp', 'LAP-ACER-A7' => 'ai-laptop-1.webp', 'LAP-GB-G5KF' => 'ai-laptop-1.webp',
        'LAP-DELL-IN15' => 'ai-laptop-2.webp', 'LAP-HP-PAV15' => 'ai-laptop-2.webp', 'LAP-APL-MBA2' => 'ai-laptop-2.webp',
        'LAP-MSI-MOD14' => 'ai-laptop-2.webp', 'LAP-LEN-E14' => 'ai-laptop-2.webp', 'LAP-ASUS-VB15' => 'ai-laptop-2.webp',
        // Graphics card
        'VGA-MSI-4060TI' => 'ai-vga-1.webp', 'VGA-GB-4080S' => 'ai-vga-1.webp', 'VGA-SAP-7800' => 'ai-vga-1.webp',
        'VGA-ZTC-4090' => 'ai-vga-1.webp', 'VGA-CLF-3070' => 'ai-vga-1.webp',
        'VGA-ASUS-4060' => 'ai-vga-2.webp', 'VGA-ASR-6600' => 'ai-vga-2.webp', 'VGA-PNY-3050' => 'ai-vga-2.webp', 'VGA-GB-1660S' => 'ai-vga-2.webp',
        // Monitor
        'MON-LG-27GP' => 'ai-monitor-1.webp', 'MON-SS-G5' => 'ai-monitor-1.webp', 'MON-AS-VG259' => 'ai-monitor-1.webp',
        'MON-LG-34WP' => 'ai-monitor-1.webp', 'MON-GB-M27Q' => 'ai-monitor-1.webp',
        'MON-DELL-S24' => 'ai-monitor-2.webp', 'MON-AOC-24G2' => 'ai-monitor-2.webp', 'MON-VS-VX27' => 'ai-monitor-2.webp',
        // Mouse
        'MOU-LOG-GPX2' => 'ai-mouse-1.webp', 'MOU-RZ-DAV3' => 'ai-mouse-1.webp', 'MOU-COR-M65' => 'ai-mouse-1.webp',
        'MOU-SS-AEROX3' => 'ai-mouse-1.webp', 'MOU-RZ-BSK3' => 'ai-mouse-1.webp', 'MOU-PUL-X2V2' => 'ai-mouse-1.webp',
        'MOU-LOG-MX3S' => 'ai-mouse-2.webp', 'MOU-MS-BT' => 'ai-mouse-2.webp',
        // RAM
        'RAM-TG-DEL16' => 'ai-ram-1.webp', 'RAM-GSK-Z5' => 'ai-ram-1.webp', 'RAM-COR-VEN32' => 'ai-ram-1.webp', 'RAM-GSK-S5' => 'ai-ram-1.webp',
        'RAM-KIN-BE16' => 'ai-ram-2.webp', 'RAM-COR-LPX8' => 'ai-ram-2.webp', 'RAM-CRU-16' => 'ai-ram-2.webp', 'RAM-KIN-LT8' => 'ai-ram-2.webp',
        // Desktop PC
        'PC-GVN-DRAG' => 'ai-pc-1.webp', 'PC-GAM-R5' => 'ai-pc-1.webp', 'PC-GFX-I7' => 'ai-pc-1.webp', 'PC-ITX-I5' => 'ai-pc-1.webp', 'PC-GVN-I9' => 'ai-pc-1.webp',
        'PC-OFF-R3' => 'ai-pc-2.webp', 'PC-STU-I5' => 'ai-pc-2.webp',
    ];

    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 1) Wipe (children first to respect FKs) so the command is repeatable.
        foreach (['App\Entity\Question', 'App\Entity\Review', 'App\Entity\OrderItem', 'App\Entity\ProductImage', 'App\Entity\Order', 'App\Entity\SanPham', 'App\Entity\Category', 'App\Entity\News', 'App\Entity\Banner', 'App\Entity\Coupon', 'App\Entity\User'] as $class) {
            $this->em->createQuery("DELETE FROM $class")->execute();
        }
        // Reset AUTO_INCREMENT so demo IDs start clean at 1.
        $conn = $this->em->getConnection();
        foreach (['question', 'review', 'order_item', 'product_image', '`order`', 'san_pham', 'category', 'news', 'banner', 'coupon', 'user'] as $table) {
            $conn->executeStatement("ALTER TABLE $table AUTO_INCREMENT = 1");
        }
        $io->text('Cleared existing data.');

        // 2) Users
        $admin = (new User())
            ->setUsername('admin')
            ->setEmail('admin@toanstore.vn')
            ->setRoles(['ROLE_ADMIN'])
            ->setFirstName('Store')->setLastName('Admin')
            ->setAddress('Toàn Store HQ')->setPhonenumber('0900000000');
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin123'));
        $this->em->persist($admin);

        $customer = (new User())
            ->setUsername('customer')
            ->setEmail('customer@toanstore.vn')
            ->setRoles(['ROLE_USER'])
            ->setFirstName('Nguyen')->setLastName('Van A')
            ->setAddress('123 Lê Lợi')->setPhonenumber('0911111111');
        $customer->setPassword($this->hasher->hashPassword($customer, 'customer123'));
        $this->em->persist($customer);

        // 3) Categories
        $catDefs = [
            'laptop' => ['Laptop', 'laptop-1.webp'],
            'pc' => ['Desktop PC', 'pc-1.png'],
            'vga' => ['Graphics Card', 'gpu-1.webp'],
            'monitor' => ['Monitor', 'monitor-1.webp'],
            'mouse' => ['Mouse', 'mouse-1.webp'],
            'ram' => ['RAM', 'ram-1.webp'],
        ];
        $cats = [];
        foreach ($catDefs as $key => [$name, $photo]) {
            $c = (new Category())->setName($name)->setPhoto($photo);
            $this->em->persist($c);
            $cats[$key] = $c;
        }

        // 4) Products
        $products = [];
        $discountPattern = [15, 0, 10, 20, 0, 8, 25, 12, 0, 18]; // % off, cycles per product
        $pi = 0;
        foreach ($this->productData() as $p) {
            $off = $discountPattern[$pi % count($discountPattern)];
            $priceOld = $off > 0 ? (int) round($p['price'] / (1 - $off / 100) / 1000) * 1000 : null;
            $pi++;
            $sp = (new SanPham())
                ->setName($p['name'])
                ->setSlug(SanPham::slugify($p['name']))
                ->setPrice($p['price'])
                ->setPriceOld($priceOld)
                ->setStock($p['stock'])
                ->setPhoto(self::AI_PHOTOS[$p['sku']] ?? $p['photo'])
                ->setBrand($p['brand'])
                ->setSku($p['sku'])
                ->setWarranty($p['warranty'])
                ->setSpecifications($p['specs'])
                ->setDescription($p['name'] . ' — chính hãng, bảo hành ' . $p['warranty'] . '.')
                ->setCate($cats[$p['cat']]);
            // one gallery image for a few products to showcase the gallery
            if (!empty($p['gallery'])) {
                foreach ($p['gallery'] as $i => $g) {
                    $sp->addImage((new ProductImage())->setFilename($g)->setPosition($i));
                }
            }
            $this->em->persist($sp);
            $products[] = $sp;
        }
        $io->text(count($products) . ' products created.');

        // 5) Reviews (by the demo customer, on the first products)
        $comments = [
            5 => 'Sản phẩm tuyệt vời, giao hàng nhanh!',
            4 => 'Chất lượng tốt so với giá tiền.',
            5 => 'Đóng gói cẩn thận, dùng rất ổn.',
            3 => 'Tạm ổn, đúng như mô tả.',
        ];
        $i = 0;
        foreach (array_slice($products, 0, 8) as $sp) {
            $rating = [5, 4, 5, 4, 3, 5, 4, 5][$i % 8];
            $this->em->persist((new Review())
                ->setProduct($sp)->setUser($customer)
                ->setRating($rating)
                ->setComment($comments[$rating] ?? 'Ổn.')
                ->setCreatedAt(new \DateTime('-' . ($i + 1) . ' days')));
            $i++;
        }

        // 6) Demo orders spread over the last 14 days (for the revenue chart).
        $provinces = ['Hà Nội', 'Hồ Chí Minh', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ'];
        $statusSeq = ['completed', 'completed', 'completed', 'shipping', 'confirmed', 'completed', 'pending', 'completed'];
        for ($i = 0; $i < 20; $i++) {
            $slice = array_slice($products, ($i * 2) % 16, 1 + ($i % 3));
            $this->makeOrder(
                $customer,
                $slice,
                $statusSeq[$i % count($statusSeq)],
                $provinces[$i % count($provinces)],
                $i % 3 === 0 ? 'bank_transfer' : 'cod',
                new \DateTime('-' . ($i % 14) . ' days')
            );
        }

        // 7) News articles
        foreach ($this->newsData() as $n) {
            $this->em->persist((new News())
                ->setTitle($n['title'])
                ->setSlug($n['slug'])
                ->setImage($n['image'])
                ->setExcerpt($n['excerpt'])
                ->setContent($n['content'])
                ->setCreatedAt(new \DateTime('-' . $n['days'] . ' days')));
        }

        // 8) Homepage banners
        // Toàn Store's own banner leads the carousel; the older stock slides follow.
        $banners = [
            ['Siêu sale công nghệ', 'images/banner-ngang.webp', '/khuyen-mai'],
            ['Banner 2', 'images/carousel-1.webp', ''],
            ['Banner 3', 'images/carousel-2.webp', ''],
            ['Banner 4', 'images/carousel-4.webp', ''],
        ];
        foreach ($banners as $pos => [$title, $img, $link]) {
            $this->em->persist((new Banner())
                ->setTitle($title)
                ->setImage($img)
                ->setLink($link)
                ->setPosition($pos)
                ->setActive(true));
        }

        // 9) Coupons
        $coupons = [
            ['GIAM10', 'percent', 10, 0, null],      // không giới hạn lượt
            ['GIAM100K', 'fixed', 100000, 2000000, 50],
            ['SALE15', 'percent', 15, 10000000, 100],
        ];
        foreach ($coupons as [$code, $type, $value, $min, $maxUses]) {
            $this->em->persist((new Coupon())
                ->setCode($code)->setType($type)->setValue($value)->setMinOrder($min)
                ->setMaxUses($maxUses)->setActive(true));
        }

        $this->em->flush();

        $io->success('Database seeded. Admin: admin / admin123 — Customer: customer / customer123 — Mã: GIAM10, GIAM100K, SALE15');

        return Command::SUCCESS;
    }

    private function makeOrder(User $user, array $products, string $status, string $province, string $payment, \DateTime $when): void
    {
        $order = (new Order())
            ->setUser($user)
            ->setCustomerName($user->getFirstName() . ' ' . $user->getLastName())
            ->setCustomerEmail('customer@example.com')
            ->setCustomerPhone($user->getPhonenumber())
            ->setCustomerAddress($user->getAddress())
            ->setShippingProvince($province)
            ->setShippingDistrict('Quận 1')
            ->setShippingWard('Phường Bến Nghé')
            ->setPaymentMethod($payment)
            ->setStatus($status)
            ->setCreatedAt($when);

        $subtotal = 0;
        foreach ($products as $sp) {
            $qty = 1;
            $item = (new OrderItem())
                ->setProduct($sp)->setQuantity($qty)->setPrice($sp->getPrice())->setOrderRef($order);
            $this->em->persist($item);
            $subtotal += $sp->getPrice() * $qty;
        }
        $fee = $subtotal >= 5_000_000 ? 0 : 40_000;
        $order->setShippingFee($fee)->setTotalPrice($subtotal + $fee);
        $this->em->persist($order);
    }

    /** @return array<int, array<string, mixed>> */
    private function productData(): array
    {
        return [
            // Laptops
            ['name' => 'Laptop Acer Nitro 5 i5-12500H RTX 3050', 'price' => 20990000, 'stock' => 12, 'brand' => 'Acer', 'sku' => 'LAP-ACER-N5', 'warranty' => '24 tháng', 'cat' => 'laptop', 'photo' => 'ming-acer-nitro-5-eagle-an515-57-5669-a1581d79b72e45239cf8ffaad2c866f4-4c42f4252471416d831cc00cd6776558-master-649466c91e698.webp', 'specs' => "CPU: Intel Core i5-12500H\nRAM: 16GB DDR4\nStorage: 512GB SSD NVMe\nGPU: RTX 3050 4GB\nMàn hình: 15.6\" FHD 144Hz"],
            ['name' => 'Laptop Asus ROG Strix G15 Ryzen 7', 'price' => 32490000, 'stock' => 6, 'brand' => 'Asus', 'sku' => 'LAP-ASUS-G15', 'warranty' => '24 tháng', 'cat' => 'laptop', 'photo' => 'ng-asus-rog-strix-g15-g513ie-hn246w-1-21909f6cb1144db7a4b8ce164efcd13f-24aa9c34626a48e8b156d240292e9045-master-64946691a68c2.webp', 'gallery' => ['pc-3-649c050fd2344.jpg'], 'specs' => "CPU: AMD Ryzen 7 6800H\nRAM: 16GB DDR5\nStorage: 1TB SSD\nGPU: RTX 3060 6GB\nMàn hình: 15.6\" 165Hz"],
            ['name' => 'Laptop MSI Katana 15 i7 RTX 4060', 'price' => 28990000, 'stock' => 8, 'brand' => 'MSI', 'sku' => 'LAP-MSI-KAT15', 'warranty' => '24 tháng', 'cat' => 'laptop', 'photo' => 'katana-15-fix-fc7f685658564fa0ae15936b185e0dfa-grande-6494633f37ab6.webp', 'gallery' => ['pro2-649452fbd113e.webp'], 'specs' => "CPU: Intel Core i7-13620H\nRAM: 16GB DDR5\nStorage: 1TB SSD\nGPU: RTX 4060 8GB\nMàn hình: 15.6\" FHD 144Hz"],
            ['name' => 'Laptop MSI GF63 Thin i5 GTX 1650', 'price' => 15490000, 'stock' => 15, 'brand' => 'MSI', 'sku' => 'LAP-MIN-1650', 'warranty' => '12 tháng', 'cat' => 'laptop', 'photo' => '664-i5-c918006d2a4e419bac95f2e5e9546ce5-7eaf7029fba946c29ebb7db3b1b1b163-grande-64b50cbf389e4.webp', 'specs' => "CPU: Intel Core i5\nRAM: 8GB\nStorage: 512GB SSD\nGPU: GTX 1650"],

            // Graphics cards
            ['name' => 'VGA Gigabyte RTX 3060 Ti Vision OC 8G', 'price' => 9990000, 'stock' => 20, 'brand' => 'Gigabyte', 'sku' => 'VGA-GB-3060TI', 'warranty' => '36 tháng', 'cat' => 'vga', 'photo' => 'eforce-rtx-3060-ti-vision-oc-8g-lhr-e254c256b32044058aeba50b8933bd20-957bc2d269d34cc0ad2908c84ed33a82-master-64b51163a5a4d.webp', 'specs' => "GPU: GeForce RTX 3060 Ti\nVRAM: 8GB GDDR6\nBoost Clock: 1665 MHz\nNguồn đề xuất: 600W"],
            ['name' => 'VGA MSI RTX 4070 Ti Gaming X', 'price' => 22990000, 'stock' => 9, 'brand' => 'MSI', 'sku' => 'VGA-MSI-4070TI', 'warranty' => '36 tháng', 'cat' => 'vga', 'photo' => 'pc-10-649c05548cd04.jpg', 'specs' => "GPU: GeForce RTX 4070 Ti\nVRAM: 12GB GDDR6X\nBoost Clock: 2610 MHz\nNguồn đề xuất: 700W"],
            ['name' => 'VGA Viper RTX 3050 White 8G', 'price' => 6490000, 'stock' => 18, 'brand' => 'Viper', 'sku' => 'VGA-VP-3050', 'warranty' => '36 tháng', 'cat' => 'vga', 'photo' => 'ai-vga-2.webp', 'specs' => "GPU: GeForce RTX 3050\nVRAM: 8GB GDDR6\nNguồn đề xuất: 550W"],
            ['name' => 'VGA Asus ROG Strix RTX 3080 OC 12G', 'price' => 18990000, 'stock' => 5, 'brand' => 'Asus', 'sku' => 'VGA-ASUS-3080', 'warranty' => '36 tháng', 'cat' => 'vga', 'photo' => 'rce-rtx-3080-gaming-oc-edition-12gb-1-3c2f3aaeb317446e8834e32466bfc572-dae9aa4ce50d4863a91c31c4a723fe6b-master-6494618aefafe.webp', 'specs' => "GPU: GeForce RTX 3080\nVRAM: 12GB GDDR6X\nNguồn đề xuất: 750W"],

            // Monitors
            ['name' => 'Màn hình Philips 24M1N3200ZA 165Hz', 'price' => 3690000, 'stock' => 25, 'brand' => 'Philips', 'sku' => 'MON-PL-24M1', 'warranty' => '24 tháng', 'cat' => 'monitor', 'photo' => 'philips-24m1n3200za-gearvn-c7107460382943a7bec4ee53ffbb3cba-46bb0febab5941c8aae871be2941f36b-grande-64b50dbe93392.webp', 'specs' => "Kích thước: 24\"\nĐộ phân giải: 1920x1080\nTần số quét: 165Hz\nTấm nền: VA\nThời gian phản hồi: 1ms"],
            ['name' => 'Màn hình Asus VA27EHF 100Hz IPS', 'price' => 3290000, 'stock' => 22, 'brand' => 'Asus', 'sku' => 'MON-AS-VA27', 'warranty' => '36 tháng', 'cat' => 'monitor', 'photo' => 'va27ehf-01-e4dfb41ce6b941829baacca40be618ac-grande-64b50d8bad375.webp', 'specs' => "Kích thước: 27\"\nĐộ phân giải: 1920x1080\nTần số quét: 100Hz\nTấm nền: IPS"],
            ['name' => 'Màn hình Gaming ViewSonic 27" 2K 144Hz', 'price' => 5490000, 'stock' => 10, 'brand' => 'ViewSonic', 'sku' => 'MON-W800-2K', 'warranty' => '24 tháng', 'cat' => 'monitor', 'photo' => 'ai-monitor-1.webp', 'specs' => "Kích thước: 27\"\nĐộ phân giải: 2560x1440\nTần số quét: 144Hz\nTấm nền: IPS"],

            // Mouse
            ['name' => 'Chuột Logitech G502 X', 'price' => 1590000, 'stock' => 40, 'brand' => 'Logitech', 'sku' => 'MOU-LOG-G502X', 'warranty' => '24 tháng', 'cat' => 'mouse', 'photo' => 'chu-log-g502x-e53ad75c630e44e9aac80c37b5a9a3dc-1164deb139cd472eade35c1b4ffe7dac-master-64b50e516cb58.webp', 'specs' => "DPI: 25600\nCảm biến: HERO 25K\nSố nút: 13\nKết nối: Có dây"],
            ['name' => 'Chuột Logitech M330 Silent Plus', 'price' => 390000, 'stock' => 60, 'brand' => 'Logitech', 'sku' => 'MOU-LOG-M330', 'warranty' => '12 tháng', 'cat' => 'mouse', 'photo' => 'logitech-m330-silent-plus-1657e3518eec422a949659f2dc30a62b-115918d6ba564a3cbc470e4a3d941001-master-64b50e7c35ffa.webp', 'specs' => "DPI: 1000\nKết nối: Wireless 2.4GHz\nPin: 24 tháng"],
            ['name' => 'Chuột Gaming Viper Mini', 'price' => 690000, 'stock' => 35, 'brand' => 'Razer', 'sku' => 'MOU-RZ-VIPER', 'warranty' => '12 tháng', 'cat' => 'mouse', 'photo' => 'ai-mouse-1.webp', 'specs' => "DPI: 8500\nTrọng lượng: 61g\nKết nối: Có dây"],

            // Desktop PC
            ['name' => 'PC Gaming GVN Phantom i5 RTX 4070', 'price' => 35990000, 'stock' => 4, 'brand' => 'GVN', 'sku' => 'PC-GVN-PHAN', 'warranty' => '36 tháng', 'cat' => 'pc', 'photo' => 'gearvn-gvn-phantom-i4070-1-146b5fdbdeb14b128d4597dac4ddc69a-master-6494620ba1773.webp', 'specs' => "CPU: Intel Core i5-13400F\nRAM: 32GB DDR5\nStorage: 1TB SSD\nGPU: RTX 4070 12GB\nNguồn: 750W 80+"],
            ['name' => 'PC Văn phòng Core i3 SSD 256GB', 'price' => 7990000, 'stock' => 14, 'brand' => 'Toàn Store', 'sku' => 'PC-OFFICE-I3', 'warranty' => '24 tháng', 'cat' => 'pc', 'photo' => 'ai-pc-2.webp', 'specs' => "CPU: Intel Core i3-12100\nRAM: 8GB DDR4\nStorage: 256GB SSD"],
            ['name' => 'PC Streaming Ryzen 7 RTX 3060', 'price' => 24990000, 'stock' => 6, 'brand' => 'Toàn Store', 'sku' => 'PC-STREAM-R7', 'warranty' => '36 tháng', 'cat' => 'pc', 'photo' => 'pc-1-649c04a668ae0.png', 'specs' => "CPU: Ryzen 7 5700X\nRAM: 16GB DDR4\nStorage: 512GB SSD\nGPU: RTX 3060 12GB"],

            // RAM
            ['name' => 'RAM Corsair Dominator RGB 32GB DDR5', 'price' => 3490000, 'stock' => 30, 'brand' => 'Corsair', 'sku' => 'RAM-COR-DOM32', 'warranty' => '36 tháng', 'cat' => 'ram', 'photo' => 'dominator-rgb-platinum-black-ddr5-01-8f962c81064143c68d9313ed53279f53-14dde4582eb34e9cb0505a1abcddc2f8-master-6494637797bec.webp', 'specs' => "Dung lượng: 32GB (2x16GB)\nBus: 6000MHz\nLoại: DDR5\nTản nhiệt: RGB"],
            ['name' => 'RAM Kingston Fury 16GB DDR4 3200', 'price' => 990000, 'stock' => 50, 'brand' => 'Kingston', 'sku' => 'RAM-KIN-16', 'warranty' => '36 tháng', 'cat' => 'ram', 'photo' => 'ai-ram-2.webp', 'specs' => "Dung lượng: 16GB\nBus: 3200MHz\nLoại: DDR4"],
            ['name' => 'RAM G.Skill Trident Z 16GB DDR4', 'price' => 1290000, 'stock' => 28, 'brand' => 'G.Skill', 'sku' => 'RAM-GSK-16', 'warranty' => '36 tháng', 'cat' => 'ram', 'photo' => 'ai-ram-1.webp', 'specs' => "Dung lượng: 16GB (2x8GB)\nBus: 3600MHz\nLoại: DDR4\nTản nhiệt: RGB"],

            // ===== 50 sản phẩm bổ sung (đa dạng thương hiệu / model / giá) =====
            // -- Laptop (10) --
            ['name' => 'Laptop Dell Inspiron 15 3530 i5-1335U', 'price' => 14490000, 'stock' => 18, 'brand' => 'Dell', 'sku' => 'LAP-DELL-IN15', 'warranty' => '12 tháng', 'cat' => 'laptop', 'photo' => 'laptop-1.webp', 'specs' => "CPU: Intel Core i5-1335U\nRAM: 16GB DDR4\nStorage: 512GB SSD\nMàn hình: 15.6\" FHD\nGPU: Intel Iris Xe"],
            ['name' => 'Laptop HP Pavilion 15 Ryzen 5 7530U', 'price' => 15990000, 'stock' => 14, 'brand' => 'HP', 'sku' => 'LAP-HP-PAV15', 'warranty' => '12 tháng', 'cat' => 'laptop', 'photo' => 'laptop-2.webp', 'specs' => "CPU: AMD Ryzen 5 7530U\nRAM: 16GB DDR4\nStorage: 512GB SSD\nMàn hình: 15.6\" FHD IPS"],
            ['name' => 'Laptop Lenovo IdeaPad Gaming 3 i5 RTX 3050', 'price' => 18990000, 'stock' => 11, 'brand' => 'Lenovo', 'sku' => 'LAP-LEN-IPG3', 'warranty' => '24 tháng', 'cat' => 'laptop', 'photo' => 'laptop-3.webp', 'specs' => "CPU: Intel Core i5-12450H\nRAM: 16GB DDR4\nStorage: 512GB SSD\nGPU: RTX 3050 4GB\nMàn hình: 15.6\" 120Hz"],
            ['name' => 'Laptop Asus TUF Gaming A15 Ryzen 7 RTX 4050', 'price' => 24490000, 'stock' => 9, 'brand' => 'Asus', 'sku' => 'LAP-ASUS-A15', 'warranty' => '24 tháng', 'cat' => 'laptop', 'photo' => 'laptop-1.webp', 'specs' => "CPU: AMD Ryzen 7 7735HS\nRAM: 16GB DDR5\nStorage: 512GB SSD\nGPU: RTX 4050 6GB\nMàn hình: 15.6\" 144Hz"],
            ['name' => 'Laptop Apple MacBook Air M2 13 inch', 'price' => 27990000, 'stock' => 10, 'brand' => 'Apple', 'sku' => 'LAP-APL-MBA2', 'warranty' => '12 tháng', 'cat' => 'laptop', 'photo' => 'laptop-2.webp', 'specs' => "Chip: Apple M2 8 nhân\nRAM: 8GB\nStorage: 256GB SSD\nMàn hình: 13.6\" Liquid Retina"],
            ['name' => 'Laptop Acer Aspire 7 i5 RTX 2050', 'price' => 16990000, 'stock' => 13, 'brand' => 'Acer', 'sku' => 'LAP-ACER-A7', 'warranty' => '12 tháng', 'cat' => 'laptop', 'photo' => 'laptop-3.webp', 'specs' => "CPU: Intel Core i5-12450H\nRAM: 8GB DDR4\nStorage: 512GB SSD\nGPU: RTX 2050 4GB"],
            ['name' => 'Laptop MSI Modern 14 C13M i5-1335U', 'price' => 13990000, 'stock' => 16, 'brand' => 'MSI', 'sku' => 'LAP-MSI-MOD14', 'warranty' => '24 tháng', 'cat' => 'laptop', 'photo' => 'laptop-1.webp', 'specs' => "CPU: Intel Core i5-1335U\nRAM: 16GB DDR4\nStorage: 512GB SSD\nMàn hình: 14\" FHD"],
            ['name' => 'Laptop Lenovo ThinkPad E14 Gen 5 i7', 'price' => 21990000, 'stock' => 7, 'brand' => 'Lenovo', 'sku' => 'LAP-LEN-E14', 'warranty' => '12 tháng', 'cat' => 'laptop', 'photo' => 'laptop-2.webp', 'specs' => "CPU: Intel Core i7-1355U\nRAM: 16GB DDR4\nStorage: 512GB SSD\nMàn hình: 14\" WUXGA"],
            ['name' => 'Laptop Gigabyte G5 KF i5 RTX 4060', 'price' => 26990000, 'stock' => 8, 'brand' => 'Gigabyte', 'sku' => 'LAP-GB-G5KF', 'warranty' => '24 tháng', 'cat' => 'laptop', 'photo' => 'laptop-3.webp', 'specs' => "CPU: Intel Core i5-12500H\nRAM: 16GB DDR5\nStorage: 512GB SSD\nGPU: RTX 4060 8GB\nMàn hình: 15.6\" 144Hz"],
            ['name' => 'Laptop Asus Vivobook 15 i3-1215U', 'price' => 10490000, 'stock' => 20, 'brand' => 'Asus', 'sku' => 'LAP-ASUS-VB15', 'warranty' => '24 tháng', 'cat' => 'laptop', 'photo' => 'laptop-1.webp', 'specs' => "CPU: Intel Core i3-1215U\nRAM: 8GB DDR4\nStorage: 256GB SSD\nMàn hình: 15.6\" FHD"],

            // -- Graphics card (9) --
            ['name' => 'VGA Asus Dual RTX 4060 OC 8G', 'price' => 8990000, 'stock' => 17, 'brand' => 'Asus', 'sku' => 'VGA-ASUS-4060', 'warranty' => '36 tháng', 'cat' => 'vga', 'photo' => 'gpu-1.webp', 'specs' => "GPU: GeForce RTX 4060\nVRAM: 8GB GDDR6\nBoost Clock: 2505 MHz\nNguồn đề xuất: 550W"],
            ['name' => 'VGA MSI RTX 4060 Ti Ventus 2X 8G', 'price' => 11990000, 'stock' => 12, 'brand' => 'MSI', 'sku' => 'VGA-MSI-4060TI', 'warranty' => '36 tháng', 'cat' => 'vga', 'photo' => 'gpu-2.webp', 'specs' => "GPU: GeForce RTX 4060 Ti\nVRAM: 8GB GDDR6\nBoost Clock: 2535 MHz\nNguồn đề xuất: 600W"],
            ['name' => 'VGA Gigabyte RTX 4080 Super Gaming OC 16G', 'price' => 29990000, 'stock' => 5, 'brand' => 'Gigabyte', 'sku' => 'VGA-GB-4080S', 'warranty' => '36 tháng', 'cat' => 'vga', 'photo' => 'gpu-3.webp', 'specs' => "GPU: GeForce RTX 4080 Super\nVRAM: 16GB GDDR6X\nBoost Clock: 2550 MHz\nNguồn đề xuất: 850W"],
            ['name' => 'VGA Colorful RTX 3070 NB 8G', 'price' => 12490000, 'stock' => 8, 'brand' => 'Colorful', 'sku' => 'VGA-CLF-3070', 'warranty' => '36 tháng', 'cat' => 'vga', 'photo' => 'gpu-1.webp', 'specs' => "GPU: GeForce RTX 3070\nVRAM: 8GB GDDR6\nNguồn đề xuất: 650W"],
            ['name' => 'VGA ASRock RX 6600 Challenger D 8G', 'price' => 5990000, 'stock' => 15, 'brand' => 'ASRock', 'sku' => 'VGA-ASR-6600', 'warranty' => '36 tháng', 'cat' => 'vga', 'photo' => 'gpu-2.webp', 'specs' => "GPU: Radeon RX 6600\nVRAM: 8GB GDDR6\nNguồn đề xuất: 500W"],
            ['name' => 'VGA Sapphire RX 7800 XT Nitro+ 16G', 'price' => 17990000, 'stock' => 6, 'brand' => 'Sapphire', 'sku' => 'VGA-SAP-7800', 'warranty' => '36 tháng', 'cat' => 'vga', 'photo' => 'gpu-3.webp', 'specs' => "GPU: Radeon RX 7800 XT\nVRAM: 16GB GDDR6\nNguồn đề xuất: 700W"],
            ['name' => 'VGA Zotac RTX 4090 Trinity 24G', 'price' => 49990000, 'stock' => 3, 'brand' => 'Zotac', 'sku' => 'VGA-ZTC-4090', 'warranty' => '36 tháng', 'cat' => 'vga', 'photo' => 'gpu-1.webp', 'specs' => "GPU: GeForce RTX 4090\nVRAM: 24GB GDDR6X\nBoost Clock: 2520 MHz\nNguồn đề xuất: 1000W"],
            ['name' => 'VGA PNY RTX 3050 Verto 6G', 'price' => 4990000, 'stock' => 22, 'brand' => 'PNY', 'sku' => 'VGA-PNY-3050', 'warranty' => '36 tháng', 'cat' => 'vga', 'photo' => 'gpu-2.webp', 'specs' => "GPU: GeForce RTX 3050\nVRAM: 6GB GDDR6\nNguồn đề xuất: 450W"],
            ['name' => 'VGA Gigabyte GTX 1660 Super OC 6G', 'price' => 4290000, 'stock' => 19, 'brand' => 'Gigabyte', 'sku' => 'VGA-GB-1660S', 'warranty' => '36 tháng', 'cat' => 'vga', 'photo' => 'gpu-3.webp', 'specs' => "GPU: GeForce GTX 1660 Super\nVRAM: 6GB GDDR6\nNguồn đề xuất: 450W"],

            // -- Monitor (8) --
            ['name' => 'Màn hình Dell S2425H 100Hz IPS 24 inch', 'price' => 3490000, 'stock' => 24, 'brand' => 'Dell', 'sku' => 'MON-DELL-S24', 'warranty' => '36 tháng', 'cat' => 'monitor', 'photo' => 'monitor-1.webp', 'specs' => "Kích thước: 24 inch\nĐộ phân giải: 1920x1080\nTần số quét: 100Hz\nTấm nền: IPS"],
            ['name' => 'Màn hình LG UltraGear 27GP750 240Hz', 'price' => 7990000, 'stock' => 9, 'brand' => 'LG', 'sku' => 'MON-LG-27GP', 'warranty' => '24 tháng', 'cat' => 'monitor', 'photo' => 'monitor-2.webp', 'specs' => "Kích thước: 27 inch\nĐộ phân giải: 1920x1080\nTần số quét: 240Hz\nTấm nền: Nano IPS\nPhản hồi: 1ms"],
            ['name' => 'Màn hình Samsung Odyssey G5 2K 165Hz', 'price' => 6490000, 'stock' => 11, 'brand' => 'Samsung', 'sku' => 'MON-SS-G5', 'warranty' => '24 tháng', 'cat' => 'monitor', 'photo' => 'monitor-3.webp', 'specs' => "Kích thước: 27 inch\nĐộ phân giải: 2560x1440\nTần số quét: 165Hz\nTấm nền: VA cong"],
            ['name' => 'Màn hình AOC 24G2SP 165Hz IPS', 'price' => 3190000, 'stock' => 20, 'brand' => 'AOC', 'sku' => 'MON-AOC-24G2', 'warranty' => '36 tháng', 'cat' => 'monitor', 'photo' => 'monitor-1.webp', 'specs' => "Kích thước: 24 inch\nĐộ phân giải: 1920x1080\nTần số quét: 165Hz\nTấm nền: IPS"],
            ['name' => 'Màn hình Gigabyte M27Q 2K 170Hz', 'price' => 7190000, 'stock' => 8, 'brand' => 'Gigabyte', 'sku' => 'MON-GB-M27Q', 'warranty' => '24 tháng', 'cat' => 'monitor', 'photo' => 'monitor-2.webp', 'specs' => "Kích thước: 27 inch\nĐộ phân giải: 2560x1440\nTần số quét: 170Hz\nTấm nền: IPS\nKVM tích hợp"],
            ['name' => 'Màn hình Asus TUF VG259QM 280Hz', 'price' => 6890000, 'stock' => 7, 'brand' => 'Asus', 'sku' => 'MON-AS-VG259', 'warranty' => '36 tháng', 'cat' => 'monitor', 'photo' => 'monitor-3.webp', 'specs' => "Kích thước: 24.5 inch\nĐộ phân giải: 1920x1080\nTần số quét: 280Hz\nTấm nền: Fast IPS"],
            ['name' => 'Màn hình ViewSonic VX2758 180Hz', 'price' => 4490000, 'stock' => 13, 'brand' => 'ViewSonic', 'sku' => 'MON-VS-VX27', 'warranty' => '24 tháng', 'cat' => 'monitor', 'photo' => 'monitor-1.webp', 'specs' => "Kích thước: 27 inch\nĐộ phân giải: 1920x1080\nTần số quét: 180Hz\nTấm nền: IPS"],
            ['name' => 'Màn hình LG 34WP65C 34 inch Ultrawide', 'price' => 8990000, 'stock' => 6, 'brand' => 'LG', 'sku' => 'MON-LG-34WP', 'warranty' => '24 tháng', 'cat' => 'monitor', 'photo' => 'monitor-2.webp', 'specs' => "Kích thước: 34 inch cong\nĐộ phân giải: 2560x1080\nTần số quét: 160Hz\nTấm nền: VA\nTỷ lệ: 21:9"],

            // -- Mouse (8) --
            ['name' => 'Chuột Logitech G Pro X Superlight 2', 'price' => 3190000, 'stock' => 25, 'brand' => 'Logitech', 'sku' => 'MOU-LOG-GPX2', 'warranty' => '24 tháng', 'cat' => 'mouse', 'photo' => 'mouse-1.webp', 'specs' => "DPI: 32000\nCảm biến: HERO 2\nTrọng lượng: 60g\nKết nối: Wireless Lightspeed"],
            ['name' => 'Chuột Razer DeathAdder V3', 'price' => 1490000, 'stock' => 30, 'brand' => 'Razer', 'sku' => 'MOU-RZ-DAV3', 'warranty' => '24 tháng', 'cat' => 'mouse', 'photo' => 'mouse-2.webp', 'specs' => "DPI: 30000\nCảm biến: Focus Pro 30K\nTrọng lượng: 59g\nKết nối: Có dây"],
            ['name' => 'Chuột Corsair M65 RGB Elite', 'price' => 1290000, 'stock' => 22, 'brand' => 'Corsair', 'sku' => 'MOU-COR-M65', 'warranty' => '24 tháng', 'cat' => 'mouse', 'photo' => 'mouse-1.webp', 'specs' => "DPI: 18000\nSố nút: 8\nTrọng lượng điều chỉnh được\nKết nối: Có dây"],
            ['name' => 'Chuột SteelSeries Aerox 3 Wireless', 'price' => 1090000, 'stock' => 18, 'brand' => 'SteelSeries', 'sku' => 'MOU-SS-AEROX3', 'warranty' => '12 tháng', 'cat' => 'mouse', 'photo' => 'mouse-2.webp', 'specs' => "DPI: 18000\nTrọng lượng: 68g\nKết nối: Wireless 2.4GHz + Bluetooth"],
            ['name' => 'Chuột Logitech MX Master 3S', 'price' => 2290000, 'stock' => 20, 'brand' => 'Logitech', 'sku' => 'MOU-LOG-MX3S', 'warranty' => '24 tháng', 'cat' => 'mouse', 'photo' => 'mouse-1.webp', 'specs' => "DPI: 8000\nCuộn MagSpeed\nKết nối: Bluetooth + USB-C\nPin: 70 ngày"],
            ['name' => 'Chuột Razer Basilisk V3', 'price' => 1390000, 'stock' => 16, 'brand' => 'Razer', 'sku' => 'MOU-RZ-BSK3', 'warranty' => '24 tháng', 'cat' => 'mouse', 'photo' => 'mouse-2.webp', 'specs' => "DPI: 26000\nSố nút: 11\nScroll Wheel HyperScroll\nKết nối: Có dây"],
            ['name' => 'Chuột Pulsar X2 V2 Wireless', 'price' => 2090000, 'stock' => 12, 'brand' => 'Pulsar', 'sku' => 'MOU-PUL-X2V2', 'warranty' => '12 tháng', 'cat' => 'mouse', 'photo' => 'mouse-1.webp', 'specs' => "DPI: 26000\nTrọng lượng: 52g\nKết nối: Wireless\nPolling: 1000Hz"],
            ['name' => 'Chuột Microsoft Bluetooth Mouse', 'price' => 490000, 'stock' => 40, 'brand' => 'Microsoft', 'sku' => 'MOU-MS-BT', 'warranty' => '12 tháng', 'cat' => 'mouse', 'photo' => 'mouse-2.webp', 'specs' => "DPI: 1000\nKết nối: Bluetooth\nThiết kế nhỏ gọn"],

            // -- RAM (8) --
            ['name' => 'RAM Corsair Vengeance 32GB DDR5 5600', 'price' => 2590000, 'stock' => 26, 'brand' => 'Corsair', 'sku' => 'RAM-COR-VEN32', 'warranty' => '36 tháng', 'cat' => 'ram', 'photo' => 'ram-1.webp', 'specs' => "Dung lượng: 32GB (2x16GB)\nBus: 5600MHz\nLoại: DDR5"],
            ['name' => 'RAM G.Skill Ripjaws S5 32GB DDR5 6000', 'price' => 2890000, 'stock' => 20, 'brand' => 'G.Skill', 'sku' => 'RAM-GSK-S5', 'warranty' => '36 tháng', 'cat' => 'ram', 'photo' => 'ram-2.webp', 'specs' => "Dung lượng: 32GB (2x16GB)\nBus: 6000MHz\nLoại: DDR5\nCL30"],
            ['name' => 'RAM Kingston Fury Beast 16GB DDR5 5200', 'price' => 1490000, 'stock' => 34, 'brand' => 'Kingston', 'sku' => 'RAM-KIN-BE16', 'warranty' => '36 tháng', 'cat' => 'ram', 'photo' => 'ram-3.webp', 'specs' => "Dung lượng: 16GB\nBus: 5200MHz\nLoại: DDR5"],
            ['name' => 'RAM TeamGroup T-Force Delta RGB 16GB DDR4', 'price' => 1090000, 'stock' => 30, 'brand' => 'TeamGroup', 'sku' => 'RAM-TG-DEL16', 'warranty' => '36 tháng', 'cat' => 'ram', 'photo' => 'ram-1.webp', 'specs' => "Dung lượng: 16GB (2x8GB)\nBus: 3200MHz\nLoại: DDR4\nTản nhiệt: RGB"],
            ['name' => 'RAM Corsair Vengeance LPX 8GB DDR4 3200', 'price' => 490000, 'stock' => 50, 'brand' => 'Corsair', 'sku' => 'RAM-COR-LPX8', 'warranty' => '36 tháng', 'cat' => 'ram', 'photo' => 'ram-2.webp', 'specs' => "Dung lượng: 8GB\nBus: 3200MHz\nLoại: DDR4"],
            ['name' => 'RAM Crucial 16GB DDR4 3200', 'price' => 790000, 'stock' => 38, 'brand' => 'Crucial', 'sku' => 'RAM-CRU-16', 'warranty' => '36 tháng', 'cat' => 'ram', 'photo' => 'ram-3.webp', 'specs' => "Dung lượng: 16GB\nBus: 3200MHz\nLoại: DDR4"],
            ['name' => 'RAM G.Skill Trident Z5 RGB 32GB DDR5 6400', 'price' => 3590000, 'stock' => 14, 'brand' => 'G.Skill', 'sku' => 'RAM-GSK-Z5', 'warranty' => '36 tháng', 'cat' => 'ram', 'photo' => 'ram-1.webp', 'specs' => "Dung lượng: 32GB (2x16GB)\nBus: 6400MHz\nLoại: DDR5\nTản nhiệt: RGB"],
            ['name' => 'RAM Laptop Kingston Fury 8GB DDR4 3200', 'price' => 590000, 'stock' => 45, 'brand' => 'Kingston', 'sku' => 'RAM-KIN-LT8', 'warranty' => '36 tháng', 'cat' => 'ram', 'photo' => 'ram-2.webp', 'specs' => "Dung lượng: 8GB SODIMM\nBus: 3200MHz\nLoại: DDR4 (Laptop)"],

            // -- Desktop PC (7) --
            ['name' => 'PC Gaming GVN Dragon i7 RTX 4070 Ti', 'price' => 45990000, 'stock' => 4, 'brand' => 'GVN', 'sku' => 'PC-GVN-DRAG', 'warranty' => '36 tháng', 'cat' => 'pc', 'photo' => 'pc-1.png', 'specs' => "CPU: Intel Core i7-14700F\nRAM: 32GB DDR5\nStorage: 1TB SSD\nGPU: RTX 4070 Ti 12GB\nNguồn: 850W 80+ Gold"],
            ['name' => 'PC Gaming Ryzen 5 RTX 4060', 'price' => 21990000, 'stock' => 8, 'brand' => 'Toàn Store', 'sku' => 'PC-GAM-R5', 'warranty' => '36 tháng', 'cat' => 'pc', 'photo' => 'pc-2.jpg', 'specs' => "CPU: Ryzen 5 5600\nRAM: 16GB DDR4\nStorage: 512GB SSD\nGPU: RTX 4060 8GB\nNguồn: 650W"],
            ['name' => 'PC Đồ họa Core i7 RTX 4070 32GB', 'price' => 38990000, 'stock' => 5, 'brand' => 'Toàn Store', 'sku' => 'PC-GFX-I7', 'warranty' => '36 tháng', 'cat' => 'pc', 'photo' => 'pc-3.png', 'specs' => "CPU: Intel Core i7-14700\nRAM: 32GB DDR5\nStorage: 1TB SSD\nGPU: RTX 4070 12GB\nNguồn: 750W Gold"],
            ['name' => 'PC Văn phòng Ryzen 3 SSD 512GB', 'price' => 8990000, 'stock' => 16, 'brand' => 'Toàn Store', 'sku' => 'PC-OFF-R3', 'warranty' => '24 tháng', 'cat' => 'pc', 'photo' => 'pc-1.png', 'specs' => "CPU: Ryzen 3 4100\nRAM: 8GB DDR4\nStorage: 512GB SSD"],
            ['name' => 'PC Mini ITX i5 RTX 4060', 'price' => 27990000, 'stock' => 6, 'brand' => 'Toàn Store', 'sku' => 'PC-ITX-I5', 'warranty' => '36 tháng', 'cat' => 'pc', 'photo' => 'pc-2.jpg', 'specs' => "CPU: Intel Core i5-13400F\nRAM: 16GB DDR5\nStorage: 1TB SSD\nGPU: RTX 4060 8GB\nCase: Mini ITX"],
            ['name' => 'PC Gaming i9 RTX 4080 Super', 'price' => 59990000, 'stock' => 3, 'brand' => 'GVN', 'sku' => 'PC-GVN-I9', 'warranty' => '36 tháng', 'cat' => 'pc', 'photo' => 'pc-3.png', 'specs' => "CPU: Intel Core i9-14900F\nRAM: 64GB DDR5\nStorage: 2TB SSD\nGPU: RTX 4080 Super 16GB\nNguồn: 1000W Gold"],
            ['name' => 'PC Học tập - Làm việc Core i5 16GB', 'price' => 11990000, 'stock' => 18, 'brand' => 'Toàn Store', 'sku' => 'PC-STU-I5', 'warranty' => '24 tháng', 'cat' => 'pc', 'photo' => 'pc-1.png', 'specs' => "CPU: Intel Core i5-12400\nRAM: 16GB DDR4\nStorage: 512GB SSD\nGPU: Intel UHD 730"],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function newsData(): array
    {
        return [
            ['slug' => 'rtx-40-series-giam-gia', 'days' => 0, 'title' => 'Loạt card RTX 40 Series giảm giá sốc cuối năm', 'image' => 'images/best1.webp',
             'excerpt' => 'Nhiều mẫu card đồ họa RTX 40 series được điều chỉnh giá tốt, cơ hội nâng cấp cấu hình gaming.',
             'content' => '<p>Bước vào mùa mua sắm cuối năm, hàng loạt card đồ họa NVIDIA RTX 40 Series được điều chỉnh mức giá hấp dẫn. Đây là thời điểm lý tưởng để game thủ nâng cấp dàn máy của mình.</p><p>Các mẫu RTX 4070, 4070 Ti mang lại hiệu năng ấn tượng ở độ phân giải 2K, trong khi RTX 4060 phù hợp cho anh em chơi game Full HD mượt mà với công nghệ DLSS 3.</p><p>Toàn Store cam kết hàng chính hãng, bảo hành 36 tháng và hỗ trợ trả góp 0%.</p>'],
            ['slug' => 'huong-dan-build-pc-gaming', 'days' => 2, 'title' => 'Hướng dẫn build PC gaming 20 triệu đáng mua nhất', 'image' => 'images/pc-1.png',
             'excerpt' => 'Gợi ý cấu hình PC gaming tầm giá 20 triệu cân mọi tựa game phổ biến hiện nay.',
             'content' => '<p>Với ngân sách 20 triệu đồng, bạn hoàn toàn có thể sở hữu một dàn PC gaming mạnh mẽ. Dưới đây là gợi ý cấu hình cân bằng giữa CPU và GPU.</p><ul><li>CPU: Intel Core i5-13400F</li><li>Card: RTX 4060 8GB</li><li>RAM: 16GB DDR4 3200MHz</li><li>SSD: 512GB NVMe</li><li>Nguồn: 650W 80+ Bronze</li></ul><p>Cấu hình này chiến tốt các tựa game AAA ở thiết lập cao.</p>'],
            ['slug' => 'laptop-van-phong-dang-mua', 'days' => 5, 'title' => 'Top laptop văn phòng mỏng nhẹ đáng mua 2024', 'image' => 'images/carousel-3.jpg',
             'excerpt' => 'Những mẫu laptop mỏng nhẹ, pin trâu, hiệu năng ổn định cho dân văn phòng.',
             'content' => '<p>Nhu cầu làm việc di động ngày càng cao khiến các mẫu laptop mỏng nhẹ trở nên hot hơn bao giờ hết. Tiêu chí lựa chọn gồm trọng lượng dưới 1.5kg, pin trên 8 tiếng và màn hình chất lượng.</p><p>Các dòng máy trang bị chip thế hệ mới cho thời lượng pin ấn tượng, phù hợp cho công việc soạn thảo, họp online và giải trí nhẹ nhàng.</p>'],
            ['slug' => 'ram-ddr5-co-dang-nang-cap', 'days' => 8, 'title' => 'RAM DDR5 đã đáng để nâng cấp chưa?', 'image' => 'images/adv1.webp',
             'excerpt' => 'So sánh DDR4 và DDR5 về hiệu năng, giá thành để quyết định có nên lên đời.',
             'content' => '<p>DDR5 mang lại băng thông cao hơn đáng kể so với DDR4, đặc biệt hữu ích với các tác vụ đa nhiệm nặng và render. Tuy nhiên với đa số game thủ, chênh lệch FPS thực tế chưa quá lớn.</p><p>Nếu bạn xây dựng cấu hình mới với nền tảng đời mới, DDR5 là lựa chọn đáng cân nhắc để bền vững lâu dài.</p>'],
            ['slug' => 'man-hinh-gaming-165hz', 'days' => 12, 'title' => 'Vì sao nên chọn màn hình gaming 165Hz?', 'image' => 'images/banner1.webp',
             'excerpt' => 'Tần số quét cao mang lại trải nghiệm chơi game mượt mà, lợi thế trong các tựa FPS.',
             'content' => '<p>Tần số quét 165Hz giúp hình ảnh chuyển động mượt mà hơn hẳn so với màn hình 60Hz truyền thống. Điều này đặc biệt quan trọng với các tựa game bắn súng đòi hỏi phản xạ nhanh.</p><p>Kết hợp cùng công nghệ đồng bộ FreeSync/G-Sync, trải nghiệm chơi game sẽ không còn hiện tượng xé hình.</p>'],
            ['slug' => 'meo-ve-sinh-may-tinh', 'days' => 16, 'title' => 'Mẹo vệ sinh máy tính đúng cách tại nhà', 'image' => 'images/banner2.webp',
             'excerpt' => 'Hướng dẫn vệ sinh bụi bẩn, tra keo tản nhiệt giúp máy mát và bền hơn.',
             'content' => '<p>Bụi bẩn tích tụ là nguyên nhân khiến máy tính nóng lên và giảm tuổi thọ linh kiện. Bạn nên vệ sinh định kỳ 3–6 tháng một lần.</p><p>Sử dụng bình khí nén để thổi bụi quạt và khe tản nhiệt, đồng thời tra lại keo tản nhiệt CPU sau 1–2 năm sử dụng.</p>'],
        ];
    }
}
