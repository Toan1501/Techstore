<?php

namespace App\Command;

use App\Entity\Review;
use App\Entity\SanPham;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Sample star ratings for the DEMO store. Every generated row is flagged `demo = true`
 * so it can be removed with `--purge` before selling to real customers
 * (posting fabricated reviews to real shoppers is prohibited by consumer-protection law).
 *
 * Output is deterministic per product (seeded by SKU), so re-running gives the same set.
 */
#[AsCommand(name: 'app:demo-reviews', description: 'Generate (or --purge) sample star ratings for the demo store')]
class DemoReviewsCommand extends Command
{
    private const SURNAMES = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Võ', 'Đặng', 'Bùi', 'Đỗ', 'Hồ', 'Ngô', 'Dương', 'Lý', 'Trịnh', 'Đinh'];
    private const MALE_MIDDLE = ['Văn', 'Quốc', 'Hữu', 'Đức', 'Minh', 'Gia', 'Anh', 'Tuấn', 'Thành', 'Hoàng'];
    private const MALE_GIVEN = ['Tuấn', 'Huy', 'Khoa', 'Long', 'Nam', 'Phúc', 'Quân', 'Thắng', 'Trung', 'Đức', 'Hải', 'Duy', 'Khánh', 'Hiếu', 'Sơn', 'Phát', 'Đạt', 'Kiên', 'Minh', 'An', 'Bảo', 'Tùng', 'Vinh'];
    private const FEMALE_MIDDLE = ['Thị', 'Ngọc', 'Thu', 'Thanh', 'Bảo', 'Hoài', 'Minh', 'Khánh'];
    private const FEMALE_GIVEN = ['Linh', 'Trang', 'Hương', 'Ngọc', 'Mai', 'Vy', 'Thảo', 'Nhi', 'Hà', 'Anh', 'Tâm', 'Hân', 'Phương', 'Yến', 'Quỳnh'];
    private const NICKS = ['huy.gaming', 'minhpc', 'tuanbuildpc', 'khoa.dev', 'longdz', 'namlaptop', 'gamer_9x', 'trangtran', 'hai.it', 'vy.design', 'phatnguyen', 'quanvo.official', 'kien.tech', 'ducanh97'];

    private const BY_CATEGORY = [
        'Laptop:gaming' => [
            'Màn hình đẹp, tần số quét cao chơi game rất đã.', 'Chơi Valorant với LOL max setting fps cao ổn định.',
            'Bàn phím gõ êm, đèn nền đẹp.', 'Tản nhiệt tốt, chơi lâu không bị nóng tay.', 'Build chắc chắn, bản lề cứng cáp.',
            'dùng đc 2 tuần chưa thấy lỗi gì, máy mát', 'Làm đồ án Photoshop với Premiere nhẹ nhàng.',
            'Quạt hơi ồn lúc chơi game nặng nhưng chấp nhận được.', 'Shop cài sẵn Win bản quyền, nhận máy là dùng luôn.',
        ],
        'Laptop:office' => [
            'Máy chạy mượt, mở Chrome chục tab với Word không giật.', 'Pin dùng văn phòng được tầm 6-7 tiếng, ổn so với tầm giá.',
            'Máy nhẹ hơn mình nghĩ, mang đi học tiện.', 'Bàn phím gõ êm, touchpad nhạy.', 'Shop cài sẵn Win bản quyền, nhận máy là dùng luôn.',
            'Màn hình hiển thị rõ, làm Excel cả ngày không mỏi mắt.', 'mua cho con đi học, máy chạy ổn',
            'Mở máy lên ngon lành, check bảo hành chính hãng ok.',
        ],
        'Desktop PC:gaming' => [
            'Shop lắp ráp gọn gàng, đi dây đẹp.', 'Chơi game 2K mượt, nhiệt độ ổn.', 'Đèn RGB đẹp lung linh, đúng như ảnh.',
            'Làm đồ họa render nhanh hơn hẳn máy cũ.', 'Kỹ thuật viên test máy kỹ trước khi giao.', 'Thùng chèn xốp kỹ, máy về nguyên vẹn.',
            'Cấu hình đúng như tư vấn, giá hợp lý.', 'stream với chơi game cùng lúc vẫn mượt',
        ],
        'Desktop PC:office' => [
            'Máy chạy êm, gần như không nghe tiếng quạt.', 'Dùng làm văn phòng cho cả công ty, ổn định.',
            'Khởi động nhanh nhờ SSD, mở Excel file nặng vẫn mượt.', 'Kỹ thuật viên test máy kỹ trước khi giao.',
            'Thùng chèn xốp kỹ, máy về nguyên vẹn.', 'Cấu hình đúng như tư vấn, giá hợp lý.', 'Nhỏ gọn, để gầm bàn vừa vặn.',
        ],
        'Graphics Card' => [
            'Card mát, chơi Cyberpunk setting cao ổn.', 'Hàng chính hãng, tem bảo hành đầy đủ.', 'Lắp vào chạy luôn, driver nhận ngon.',
            'Nhiệt độ load tầm 65-70 độ, quạt êm.', 'Thiết kế đẹp, lên case nhìn rất đã.', 'Lên đời từ card cũ, fps tăng gấp đôi.',
            'card đẹp, hộp còn seal nguyên', 'Render Blender nhanh hẳn, rất đáng tiền.',
        ],
        'Monitor' => [
            'Màu đẹp, không có điểm chết.', 'Tần số quét cao chơi FPS sướng hẳn.', 'Chân đế chắc chắn.',
            'Màn mỏng, viền nhỏ, setup bàn làm việc rất đẹp.', 'Đọc chữ nét, làm văn phòng cả ngày không mỏi mắt.',
            'Shop test điểm chết trước khi giao, yên tâm.', 'xem phim chơi game đều ổn, giá tốt', 'Màu sắc chuẩn, chỉnh ảnh khá ổn.',
        ],
        'Mouse' => [
            'Cầm vừa tay, click nảy.', 'Phần mềm chỉnh DPI dễ dùng.', 'Feet chuột lướt êm, aim chuẩn hơn hẳn.',
            'chuột đẹp, cầm chắc tay', 'Dùng cả ngày không mỏi tay.',
        ],
        'Mouse:wireless' => [
            'Kết nối không dây ổn định, không bị delay.', 'Pin trâu, dùng cả tuần mới sạc.', 'Nhẹ, lia rất sướng.',
        ],
        'RAM' => [
            'Lắp vào nhận đủ dung lượng, chạy đúng bus.', 'Nâng cấp xong máy mượt hẳn.', 'Chạy dual channel ổn định, không lỗi.',
            'Hàng chính hãng, bảo hành dài yên tâm.', 'ram chạy ngon, test memtest không lỗi', 'Đa nhiệm thoải mái hơn hẳn.',
        ],
    ];
    private const GENERIC_GOOD = [
        'Hàng chuẩn chính hãng, giao nhanh.', 'Shop tư vấn nhiệt tình, sẽ ủng hộ tiếp.', 'Đóng gói cẩn thận, sản phẩm như mô tả.',
        'Giá tốt hơn mấy chỗ khác mình tham khảo.', 'Rất hài lòng.', 'sp ok, giao nhanh', 'Mua lần 2 rồi, vẫn ưng.',
        'Nhân viên gọi xác nhận đơn rất nhanh.', 'Giao hàng trong ngày luôn, quá nhanh.', 'Đáng tiền.', 'Tốt, đúng như quảng cáo.',
    ];
    /** Optional tail sentence => keyword that means the main comment already says it. */
    private const EXTRAS = [' Giao hàng nhanh.' => 'giao', ' Đóng gói kỹ.' => 'đóng gói', ' Shop tư vấn nhiệt tình.' => 'tư vấn', ' Sẽ ủng hộ shop tiếp.' => 'ủng hộ', ' Giá ổn.' => 'giá'];
    private const MEDIUM = [
        'Sản phẩm ổn nhưng giao hàng hơi chậm 1 ngày.', 'Dùng tạm ổn, chưa thấy gì nổi bật so với giá.',
        'Hộp hơi móp nhưng sản phẩm bên trong không sao.', 'Ổn, nhưng hướng dẫn sử dụng hơi sơ sài.', 'Hàng tốt, có điều phải chờ hàng về 3 ngày.',
    ];
    private const LOW = [
        'Giao thiếu phụ kiện, shop đã gửi bù nhưng mất thời gian.', 'Hơi khác kỳ vọng so với ảnh, nhưng shop hỗ trợ đổi nhanh.',
    ];

    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('purge', null, InputOption::VALUE_NONE, 'Delete all demo reviews and stop');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $removed = $this->em->createQuery('DELETE FROM ' . Review::class . ' r WHERE r.demo = true')->execute();
        if ($input->getOption('purge')) {
            $io->success("Đã xóa $removed đánh giá mẫu.");

            return Command::SUCCESS;
        }

        $now = new \DateTimeImmutable('-30 minutes');
        $total = 0;
        foreach ($this->em->getRepository(SanPham::class)->findAll() as $p) {
            mt_srand(crc32((string) ($p->getSku() ?: $p->getId())));

            // Cheaper and discounted products collect more reviews.
            $price = (float) $p->getPrice();
            $base = $price < 1_000_000 ? 14 : ($price < 5_000_000 ? 10 : ($price < 20_000_000 ? 7 : 4));
            $count = max(2, $base + mt_rand(-3, 8) + ($p->hasDiscount() ? mt_rand(1, 4) : 0));
            $quality = mt_rand(78, 97); // % of 5-star among the happy majority

            $pool = array_merge($this->categoryPool($p), self::GENERIC_GOOD);
            shuffle($pool);
            $used = [];

            for ($i = 0; $i < $count; $i++) {
                $roll = mt_rand(1, 100);
                $rating = $roll <= 3 ? 2 : ($roll <= 11 ? 3 : (mt_rand(1, 100) <= $quality ? 5 : 4));

                $comment = null;
                if (mt_rand(1, 100) > 18) { // ~18% leave stars only
                    if ($rating <= 2) {
                        $comment = self::LOW[array_rand(self::LOW)];
                    } elseif ($rating === 3) {
                        $comment = self::MEDIUM[array_rand(self::MEDIUM)];
                    } else {
                        $comment = $pool[$i % count($pool)];
                        if (isset($used[$comment])) {
                            $comment = $pool[mt_rand(0, count($pool) - 1)];
                        }
                        if (mt_rand(1, 100) <= 35) {
                            $tail = array_rand(self::EXTRAS);
                            if (mb_stripos($comment, self::EXTRAS[$tail]) === false) {
                                $comment = rtrim($comment, '.') . '.' . $tail;
                            }
                        }
                        $used[$comment] = true;
                    }
                }

                $daysAgo = (int) round(240 * (mt_rand(0, 1000) / 1000) ** 1.6); // skew towards recent
                $at = $now->modify("-$daysAgo days")->setTime(mt_rand(7, 22), mt_rand(0, 59));
                if ($at > $now) {
                    $at = $now->modify('-' . mt_rand(20, 300) . ' minutes');
                }

                $this->em->persist((new Review())
                    ->setProduct($p)
                    ->setAuthorName($this->name())
                    ->setRating($rating)
                    ->setComment($comment)
                    ->setCreatedAt(\DateTime::createFromImmutable($at))
                    ->setDemo(true));
                $total++;
            }
        }
        mt_srand();
        $this->em->flush();

        $io->success("Đã tạo $total đánh giá mẫu (xóa $removed bản cũ). Gỡ toàn bộ bằng: app:demo-reviews --purge");

        return Command::SUCCESS;
    }

    /** Category comments, narrowed to gaming/office or wireless where it matters. */
    private function categoryPool(SanPham $p): array
    {
        $cat = (string) $p->getCate()?->getName();
        $name = (string) $p->getName();
        if ($cat === 'Laptop' || $cat === 'Desktop PC') {
            $gaming = (bool) preg_match('/gaming|rtx|gtx|rog|tuf|katana|nitro|g5|streaming|đồ họa|itx/iu', $name);

            return self::BY_CATEGORY[$cat . ($gaming ? ':gaming' : ':office')];
        }
        $pool = self::BY_CATEGORY[$cat] ?? [];
        if ($cat === 'Mouse' && preg_match('/wireless|bluetooth|superlight|mx master|m330|pulsar|aerox/i', $name)) {
            $pool = array_merge($pool, self::BY_CATEGORY['Mouse:wireless']);
        }

        return $pool;
    }

    private function name(): string
    {
        $s = self::SURNAMES[array_rand(self::SURNAMES)];
        $male = mt_rand(1, 100) <= 72; // tech store audience
        $middles = $male ? self::MALE_MIDDLE : self::FEMALE_MIDDLE;
        $givens = $male ? self::MALE_GIVEN : self::FEMALE_GIVEN;
        $m = $middles[array_rand($middles)];
        $g = $givens[array_rand($givens)];
        if ($m === $g) {
            $m = $male ? 'Văn' : 'Thị';
        }

        return match (mt_rand(1, 10)) {
            1, 2, 3 => "$s $m $g",
            4, 5, 6 => in_array($m, ['Văn', 'Thị'], true) ? "$g " . mb_substr($s, 0, 1) . '.' : "$m $g",
            7, 8 => "$g " . mb_substr($s, 0, 1) . '.',
            default => self::NICKS[array_rand(self::NICKS)],
        };
    }
}
