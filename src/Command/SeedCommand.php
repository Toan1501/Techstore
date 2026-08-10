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
            ->setRoles(['ROLE_ADMIN'])
            ->setFirstName('Store')->setLastName('Admin')
            ->setAddress('Techstore HQ')->setPhonenumber('0900000000');
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin123'));
        $this->em->persist($admin);

        $customer = (new User())
            ->setUsername('customer')
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
                ->setPhoto($p['photo'])
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
        $bannerImgs = ['images/carousel-1.webp', 'images/carousel-2.webp', 'images/carousel-4.webp'];
        foreach ($bannerImgs as $pos => $img) {
            $this->em->persist((new Banner())
                ->setTitle('Banner ' . ($pos + 1))
                ->setImage($img)
                ->setLink('')
                ->setPosition($pos)
                ->setActive(true));
        }

        // 9) Coupons
        $coupons = [
            ['GIAM10', 'percent', 10, 0],
            ['GIAM100K', 'fixed', 100000, 2000000],
            ['SALE15', 'percent', 15, 10000000],
        ];
        foreach ($coupons as [$code, $type, $value, $min]) {
            $this->em->persist((new Coupon())
                ->setCode($code)->setType($type)->setValue($value)->setMinOrder($min)->setActive(true));
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
            ['name' => 'Laptop Acer Nitro 5 i5-12500H RTX 3050', 'price' => 20990000, 'stock' => 12, 'brand' => 'Acer', 'sku' => 'LAP-ACER-N5', 'warranty' => '24 tháng', 'cat' => 'laptop', 'photo' => 'laptop-1.webp', 'gallery' => ['laptop-2.webp', 'laptop-3.webp'], 'specs' => "CPU: Intel Core i5-12500H\nRAM: 16GB DDR4\nStorage: 512GB SSD NVMe\nGPU: RTX 3050 4GB\nMàn hình: 15.6\" FHD 144Hz"],
            ['name' => 'Laptop Asus ROG Strix G15 Ryzen 7', 'price' => 32490000, 'stock' => 6, 'brand' => 'Asus', 'sku' => 'LAP-ASUS-G15', 'warranty' => '24 tháng', 'cat' => 'laptop', 'photo' => 'laptop-2.webp', 'specs' => "CPU: AMD Ryzen 7 6800H\nRAM: 16GB DDR5\nStorage: 1TB SSD\nGPU: RTX 3060 6GB\nMàn hình: 15.6\" 165Hz"],
            ['name' => 'Laptop MSI Katana 15 i7 RTX 4060', 'price' => 28990000, 'stock' => 8, 'brand' => 'MSI', 'sku' => 'LAP-MSI-KAT15', 'warranty' => '24 tháng', 'cat' => 'laptop', 'photo' => 'laptop-3.webp', 'specs' => "CPU: Intel Core i7-13620H\nRAM: 16GB DDR5\nStorage: 1TB SSD\nGPU: RTX 4060 8GB\nMàn hình: 15.6\" FHD 144Hz"],
            ['name' => 'Laptop Gaming Minion i5 GTX 1650', 'price' => 15490000, 'stock' => 15, 'brand' => 'Acer', 'sku' => 'LAP-MIN-1650', 'warranty' => '12 tháng', 'cat' => 'laptop', 'photo' => 'laptop-1.webp', 'specs' => "CPU: Intel Core i5\nRAM: 8GB\nStorage: 512GB SSD\nGPU: GTX 1650"],

            // Graphics cards
            ['name' => 'VGA Gigabyte RTX 3060 Ti Vision OC 8G', 'price' => 9990000, 'stock' => 20, 'brand' => 'Gigabyte', 'sku' => 'VGA-GB-3060TI', 'warranty' => '36 tháng', 'cat' => 'vga', 'photo' => 'gpu-1.webp', 'gallery' => ['gpu-2.webp'], 'specs' => "GPU: GeForce RTX 3060 Ti\nVRAM: 8GB GDDR6\nBoost Clock: 1665 MHz\nNguồn đề xuất: 600W"],
            ['name' => 'VGA MSI RTX 4070 Ti Gaming X', 'price' => 22990000, 'stock' => 9, 'brand' => 'MSI', 'sku' => 'VGA-MSI-4070TI', 'warranty' => '36 tháng', 'cat' => 'vga', 'photo' => 'gpu-2.webp', 'specs' => "GPU: GeForce RTX 4070 Ti\nVRAM: 12GB GDDR6X\nBoost Clock: 2610 MHz\nNguồn đề xuất: 700W"],
            ['name' => 'VGA Viper RTX 3050 White 8G', 'price' => 6490000, 'stock' => 18, 'brand' => 'Viper', 'sku' => 'VGA-VP-3050', 'warranty' => '36 tháng', 'cat' => 'vga', 'photo' => 'gpu-3.webp', 'specs' => "GPU: GeForce RTX 3050\nVRAM: 8GB GDDR6\nNguồn đề xuất: 550W"],
            ['name' => 'VGA Gigabyte RTX 3080 Gaming OC 12G', 'price' => 18990000, 'stock' => 5, 'brand' => 'Gigabyte', 'sku' => 'VGA-GB-3080', 'warranty' => '36 tháng', 'cat' => 'vga', 'photo' => 'gpu-1.webp', 'specs' => "GPU: GeForce RTX 3080\nVRAM: 12GB GDDR6X\nNguồn đề xuất: 750W"],

            // Monitors
            ['name' => 'Màn hình Philips 24M1N3200ZA 165Hz', 'price' => 3690000, 'stock' => 25, 'brand' => 'Philips', 'sku' => 'MON-PL-24M1', 'warranty' => '24 tháng', 'cat' => 'monitor', 'photo' => 'monitor-1.webp', 'gallery' => ['monitor-2.webp'], 'specs' => "Kích thước: 24\"\nĐộ phân giải: 1920x1080\nTần số quét: 165Hz\nTấm nền: VA\nThời gian phản hồi: 1ms"],
            ['name' => 'Màn hình Asus VA27EHF 100Hz IPS', 'price' => 3290000, 'stock' => 22, 'brand' => 'Asus', 'sku' => 'MON-AS-VA27', 'warranty' => '36 tháng', 'cat' => 'monitor', 'photo' => 'monitor-2.webp', 'specs' => "Kích thước: 27\"\nĐộ phân giải: 1920x1080\nTần số quét: 100Hz\nTấm nền: IPS"],
            ['name' => 'Màn hình Gaming W800 27" 2K 144Hz', 'price' => 5490000, 'stock' => 10, 'brand' => 'ViewSonic', 'sku' => 'MON-W800-2K', 'warranty' => '24 tháng', 'cat' => 'monitor', 'photo' => 'monitor-3.webp', 'specs' => "Kích thước: 27\"\nĐộ phân giải: 2560x1440\nTần số quét: 144Hz\nTấm nền: IPS"],

            // Mouse
            ['name' => 'Chuột Logitech G502 X', 'price' => 1590000, 'stock' => 40, 'brand' => 'Logitech', 'sku' => 'MOU-LOG-G502X', 'warranty' => '24 tháng', 'cat' => 'mouse', 'photo' => 'mouse-1.webp', 'specs' => "DPI: 25600\nCảm biến: HERO 25K\nSố nút: 13\nKết nối: Có dây"],
            ['name' => 'Chuột Logitech M330 Silent Plus', 'price' => 390000, 'stock' => 60, 'brand' => 'Logitech', 'sku' => 'MOU-LOG-M330', 'warranty' => '12 tháng', 'cat' => 'mouse', 'photo' => 'mouse-2.webp', 'specs' => "DPI: 1000\nKết nối: Wireless 2.4GHz\nPin: 24 tháng"],
            ['name' => 'Chuột Gaming Viper Mini', 'price' => 690000, 'stock' => 35, 'brand' => 'Razer', 'sku' => 'MOU-RZ-VIPER', 'warranty' => '12 tháng', 'cat' => 'mouse', 'photo' => 'mouse-1.webp', 'specs' => "DPI: 8500\nTrọng lượng: 61g\nKết nối: Có dây"],

            // Desktop PC
            ['name' => 'PC Gaming GVN Phantom i5 RTX 4070', 'price' => 35990000, 'stock' => 4, 'brand' => 'GVN', 'sku' => 'PC-GVN-PHAN', 'warranty' => '36 tháng', 'cat' => 'pc', 'photo' => 'pc-1.png', 'gallery' => ['pc-2.jpg', 'pc-3.png'], 'specs' => "CPU: Intel Core i5-13400F\nRAM: 32GB DDR5\nStorage: 1TB SSD\nGPU: RTX 4070 12GB\nNguồn: 750W 80+"],
            ['name' => 'PC Văn phòng Core i3 SSD 256GB', 'price' => 7990000, 'stock' => 14, 'brand' => 'Techstore', 'sku' => 'PC-OFFICE-I3', 'warranty' => '24 tháng', 'cat' => 'pc', 'photo' => 'pc-2.jpg', 'specs' => "CPU: Intel Core i3-12100\nRAM: 8GB DDR4\nStorage: 256GB SSD"],
            ['name' => 'PC Streaming Ryzen 7 RTX 3060', 'price' => 24990000, 'stock' => 6, 'brand' => 'Techstore', 'sku' => 'PC-STREAM-R7', 'warranty' => '36 tháng', 'cat' => 'pc', 'photo' => 'pc-3.png', 'specs' => "CPU: Ryzen 7 5700X\nRAM: 16GB DDR4\nStorage: 512GB SSD\nGPU: RTX 3060 12GB"],

            // RAM
            ['name' => 'RAM Corsair Dominator RGB 32GB DDR5', 'price' => 3490000, 'stock' => 30, 'brand' => 'Corsair', 'sku' => 'RAM-COR-DOM32', 'warranty' => '36 tháng', 'cat' => 'ram', 'photo' => 'ram-1.webp', 'specs' => "Dung lượng: 32GB (2x16GB)\nBus: 6000MHz\nLoại: DDR5\nTản nhiệt: RGB"],
            ['name' => 'RAM Kingston Fury 16GB DDR4 3200', 'price' => 990000, 'stock' => 50, 'brand' => 'Kingston', 'sku' => 'RAM-KIN-16', 'warranty' => '36 tháng', 'cat' => 'ram', 'photo' => 'ram-2.webp', 'specs' => "Dung lượng: 16GB\nBus: 3200MHz\nLoại: DDR4"],
            ['name' => 'RAM G.Skill Trident Z 16GB DDR4', 'price' => 1290000, 'stock' => 28, 'brand' => 'G.Skill', 'sku' => 'RAM-GSK-16', 'warranty' => '36 tháng', 'cat' => 'ram', 'photo' => 'ram-3.webp', 'specs' => "Dung lượng: 16GB (2x8GB)\nBus: 3600MHz\nLoại: DDR4\nTản nhiệt: RGB"],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function newsData(): array
    {
        return [
            ['slug' => 'rtx-40-series-giam-gia', 'days' => 0, 'title' => 'Loạt card RTX 40 Series giảm giá sốc cuối năm', 'image' => 'images/best1.webp',
             'excerpt' => 'Nhiều mẫu card đồ họa RTX 40 series được điều chỉnh giá tốt, cơ hội nâng cấp cấu hình gaming.',
             'content' => '<p>Bước vào mùa mua sắm cuối năm, hàng loạt card đồ họa NVIDIA RTX 40 Series được điều chỉnh mức giá hấp dẫn. Đây là thời điểm lý tưởng để game thủ nâng cấp dàn máy của mình.</p><p>Các mẫu RTX 4070, 4070 Ti mang lại hiệu năng ấn tượng ở độ phân giải 2K, trong khi RTX 4060 phù hợp cho anh em chơi game Full HD mượt mà với công nghệ DLSS 3.</p><p>Techstore cam kết hàng chính hãng, bảo hành 36 tháng và hỗ trợ trả góp 0%.</p>'],
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
