<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Static SEO content pages (about, policies, guides, FAQ).
 * Each has a clean Vietnamese slug, unique title + meta description,
 * breadcrumb + BreadcrumbList structured data, and internal links.
 */
class PageController extends AbstractController
{
    /** @return array<string, array{route:string,nav:string,title:string,meta:string,body:string}> */
    public static function pages(): array
    {
        return [
            'about' => [
                'route' => 'page_about', 'nav' => 'Giới thiệu',
                'title' => 'Giới thiệu về Toàn Store',
                'meta' => 'Toàn Store — hệ thống bán lẻ máy tính, laptop, PC gaming và linh kiện chính hãng, giá tốt, bảo hành uy tín, trả góp 0%.',
                'body' => '<p><strong>Toàn Store</strong> là hệ thống bán lẻ máy tính, laptop, PC gaming và linh kiện công nghệ chính hãng. Chúng tôi mang đến sản phẩm chất lượng, giá cạnh tranh cùng dịch vụ hậu mãi tận tâm.</p>'
                    . '<h2>Giá trị cốt lõi</h2><ul><li>100% sản phẩm chính hãng, nguồn gốc rõ ràng.</li><li>Bảo hành uy tín, đổi trả linh hoạt trong 7 ngày.</li><li>Tư vấn cấu hình miễn phí theo nhu cầu và ngân sách.</li><li>Giao hàng toàn quốc, miễn phí cho đơn từ 5 triệu.</li></ul>'
                    . '<h2>Tầm nhìn</h2><p>Trở thành địa chỉ mua sắm công nghệ đáng tin cậy hàng đầu, đồng hành cùng game thủ, dân văn phòng và nhà sáng tạo nội dung Việt Nam.</p>',
            ],
            'warranty' => [
                'route' => 'page_warranty', 'nav' => 'Chính sách bảo hành',
                'title' => 'Chính sách bảo hành',
                'meta' => 'Chính sách bảo hành của Toàn Store: thời hạn, điều kiện và quy trình bảo hành máy tính, laptop, linh kiện chính hãng.',
                'body' => '<p>Tất cả sản phẩm tại Toàn Store được bảo hành chính hãng theo quy định của nhà sản xuất.</p>'
                    . '<h2>Thời hạn bảo hành</h2><ul><li>Laptop, PC: 12–36 tháng tùy sản phẩm.</li><li>Linh kiện (VGA, RAM, mainboard…): 36 tháng.</li><li>Phụ kiện (chuột, bàn phím…): 12–24 tháng.</li></ul>'
                    . '<h2>Điều kiện bảo hành</h2><ul><li>Sản phẩm còn trong thời hạn bảo hành, còn tem/phiếu bảo hành.</li><li>Lỗi do nhà sản xuất, không do tác động vật lý, vào nước, cháy nổ.</li></ul>'
                    . '<h2>Quy trình</h2><p>Mang sản phẩm đến cửa hàng hoặc liên hệ hotline để được hướng dẫn gửi bảo hành. Thời gian xử lý 3–14 ngày tùy trường hợp.</p>',
            ],
            'return' => [
                'route' => 'page_return', 'nav' => 'Chính sách đổi trả',
                'title' => 'Chính sách đổi trả',
                'meta' => 'Chính sách đổi trả hàng 7 ngày của Toàn Store: điều kiện 1 đổi 1, hoàn tiền với sản phẩm lỗi nhà sản xuất.',
                'body' => '<p>Toàn Store hỗ trợ <strong>đổi trả trong 7 ngày</strong> kể từ ngày nhận hàng với sản phẩm lỗi do nhà sản xuất.</p>'
                    . '<h2>Điều kiện đổi trả</h2><ul><li>Sản phẩm còn nguyên vẹn, đầy đủ hộp, phụ kiện, phiếu bảo hành.</li><li>Có hóa đơn hoặc thông tin đơn hàng.</li><li>Lỗi kỹ thuật từ nhà sản xuất: đổi mới 1–1.</li></ul>'
                    . '<h2>Trường hợp hoàn tiền</h2><p>Nếu hết hàng cùng loại để đổi, chúng tôi hoàn tiền 100% qua phương thức bạn đã thanh toán.</p>',
            ],
            'shipping' => [
                'route' => 'page_shipping', 'nav' => 'Chính sách giao hàng',
                'title' => 'Chính sách giao hàng',
                'meta' => 'Chính sách giao hàng toàn quốc của Toàn Store: phí vận chuyển, thời gian giao, miễn phí ship cho đơn từ 5 triệu.',
                'body' => '<p>Toàn Store giao hàng toàn quốc, nhanh chóng và an toàn.</p>'
                    . '<h2>Phí & thời gian</h2><ul><li>Nội thành: 25.000₫ – giao trong 1–2 ngày.</li><li>Tỉnh khác: 40.000₫ – giao 2–5 ngày.</li><li><strong>Miễn phí vận chuyển</strong> cho đơn hàng từ 5.000.000₫.</li></ul>'
                    . '<h2>Kiểm tra khi nhận hàng</h2><p>Quý khách được đồng kiểm (mở hộp kiểm tra ngoại quan) trước khi thanh toán với đơn COD.</p>',
            ],
            'privacy' => [
                'route' => 'page_privacy', 'nav' => 'Chính sách bảo mật',
                'title' => 'Chính sách bảo mật thông tin',
                'meta' => 'Chính sách bảo mật của Toàn Store: cách chúng tôi thu thập, sử dụng và bảo vệ thông tin cá nhân của khách hàng.',
                'body' => '<p>Toàn Store cam kết bảo vệ thông tin cá nhân của khách hàng.</p>'
                    . '<h2>Thông tin thu thập</h2><p>Họ tên, số điện thoại, email, địa chỉ giao hàng — chỉ dùng để xử lý đơn hàng và chăm sóc khách hàng.</p>'
                    . '<h2>Cam kết</h2><ul><li>Không mua bán, trao đổi thông tin khách hàng cho bên thứ ba.</li><li>Dữ liệu được lưu trữ an toàn, mã hóa mật khẩu.</li><li>Khách hàng có quyền yêu cầu chỉnh sửa hoặc xóa thông tin.</li></ul>',
            ],
            'terms' => [
                'route' => 'page_terms', 'nav' => 'Điều khoản sử dụng',
                'title' => 'Điều khoản sử dụng',
                'meta' => 'Điều khoản sử dụng website Toàn Store: quyền và trách nhiệm của khách hàng khi mua sắm trực tuyến.',
                'body' => '<p>Khi sử dụng website Toàn Store, quý khách đồng ý với các điều khoản sau.</p>'
                    . '<ul><li>Thông tin đặt hàng phải chính xác và trung thực.</li><li>Giá và khuyến mãi có thể thay đổi theo thời điểm; đơn hàng được xác nhận qua điện thoại.</li><li>Nội dung, hình ảnh trên website thuộc quyền sở hữu của Toàn Store.</li><li>Nghiêm cấm hành vi phá hoại, gian lận hoặc truy cập trái phép.</li></ul>',
            ],
            'buyguide' => [
                'route' => 'page_buyguide', 'nav' => 'Hướng dẫn mua hàng',
                'title' => 'Hướng dẫn mua hàng online',
                'meta' => 'Hướng dẫn mua hàng online tại Toàn Store chỉ với 4 bước đơn giản: chọn sản phẩm, thêm giỏ, điền thông tin, đặt hàng.',
                'body' => '<h2>4 bước đặt hàng</h2><ol><li>Chọn sản phẩm và bấm <strong>Thêm vào giỏ</strong> hoặc <strong>Mua ngay</strong>.</li><li>Vào giỏ hàng, kiểm tra số lượng, nhập mã giảm giá (nếu có).</li><li>Bấm <strong>Thanh toán</strong>, điền thông tin giao hàng.</li><li>Chọn phương thức thanh toán và bấm <strong>Đặt hàng</strong>.</li></ol>'
                    . '<p>Sau khi đặt, nhân viên sẽ gọi xác nhận đơn hàng trong thời gian sớm nhất.</p>',
            ],
            'payguide' => [
                'route' => 'page_payguide', 'nav' => 'Hướng dẫn thanh toán',
                'title' => 'Hướng dẫn thanh toán',
                'meta' => 'Các hình thức thanh toán tại Toàn Store: COD nhận hàng trả tiền, chuyển khoản ngân hàng và ví MoMo online.',
                'body' => '<h2>Các hình thức thanh toán</h2><ul><li><strong>COD</strong> — thanh toán tiền mặt khi nhận hàng.</li><li><strong>Chuyển khoản ngân hàng</strong> — theo thông tin hiển thị khi đặt hàng, nội dung ghi mã đơn.</li><li><strong>Ví MoMo</strong> — thanh toán online nhanh chóng, an toàn.</li></ul>'
                    . '<p>Mọi giao dịch đều được bảo mật. Vui lòng giữ lại biên lai/chứng từ chuyển khoản để đối soát.</p>',
            ],
            'installment' => [
                'route' => 'page_installment', 'nav' => 'Hướng dẫn trả góp',
                'title' => 'Hướng dẫn mua trả góp 0%',
                'meta' => 'Mua trả góp 0% lãi suất tại Toàn Store qua thẻ tín dụng, thủ tục nhanh gọn cho laptop, PC gaming và linh kiện.',
                'body' => '<p>Toàn Store hỗ trợ <strong>trả góp 0% lãi suất</strong> qua thẻ tín dụng của các ngân hàng.</p>'
                    . '<h2>Điều kiện</h2><ul><li>Có thẻ tín dụng còn hạn mức.</li><li>Giá trị đơn hàng tối thiểu theo quy định ngân hàng.</li><li>Kỳ hạn 3–12 tháng.</li></ul>'
                    . '<p>Liên hệ hotline để được tư vấn gói trả góp phù hợp với sản phẩm bạn chọn.</p>',
            ],
            'careers' => [
                'route' => 'page_careers', 'nav' => 'Tuyển dụng',
                'title' => 'Tuyển dụng tại Toàn Store',
                'meta' => 'Cơ hội nghề nghiệp tại Toàn Store: nhân viên kinh doanh, kỹ thuật viên, marketing. Môi trường trẻ trung, năng động.',
                'body' => '<p>Toàn Store luôn chào đón những ứng viên đam mê công nghệ gia nhập đội ngũ.</p>'
                    . '<h2>Vị trí thường tuyển</h2><ul><li>Nhân viên tư vấn bán hàng.</li><li>Kỹ thuật viên lắp ráp – bảo hành.</li><li>Nhân viên Marketing / Content.</li></ul>'
                    . '<p>Ứng viên quan tâm vui lòng gửi CV qua email hoặc liên hệ hotline của cửa hàng.</p>',
            ],
        ];
    }

    #[Route('/gioi-thieu', name: 'page_about')]
    public function about(): Response { return $this->show('about'); }

    #[Route('/chinh-sach-bao-hanh', name: 'page_warranty')]
    public function warranty(): Response { return $this->show('warranty'); }

    #[Route('/chinh-sach-doi-tra', name: 'page_return')]
    public function return_(): Response { return $this->show('return'); }

    #[Route('/chinh-sach-giao-hang', name: 'page_shipping')]
    public function shipping(): Response { return $this->show('shipping'); }

    #[Route('/chinh-sach-bao-mat', name: 'page_privacy')]
    public function privacy(): Response { return $this->show('privacy'); }

    #[Route('/dieu-khoan-su-dung', name: 'page_terms')]
    public function terms(): Response { return $this->show('terms'); }

    #[Route('/huong-dan-mua-hang', name: 'page_buyguide')]
    public function buyguide(): Response { return $this->show('buyguide'); }

    #[Route('/huong-dan-thanh-toan', name: 'page_payguide')]
    public function payguide(): Response { return $this->show('payguide'); }

    #[Route('/huong-dan-tra-gop', name: 'page_installment')]
    public function installment(): Response { return $this->show('installment'); }

    #[Route('/tuyen-dung', name: 'page_careers')]
    public function careers(): Response { return $this->show('careers'); }

    private function show(string $key): Response
    {
        $pages = self::pages();

        return $this->render('page/show.html.twig', [
            'page' => $pages[$key],
            'current' => $key,
            'pages' => $pages,
        ]);
    }

    #[Route('/cau-hoi-thuong-gap', name: 'page_faq')]
    public function faq(): Response
    {
        $faqs = [
            ['q' => 'Sản phẩm tại Toàn Store có phải hàng chính hãng không?', 'a' => 'Tất cả sản phẩm đều là hàng chính hãng 100%, có đầy đủ hóa đơn và phiếu bảo hành theo nhà sản xuất.'],
            ['q' => 'Thời gian giao hàng là bao lâu?', 'a' => 'Nội thành 1–2 ngày, tỉnh khác 2–5 ngày. Miễn phí vận chuyển cho đơn hàng từ 5 triệu đồng.'],
            ['q' => 'Tôi có được kiểm tra hàng trước khi thanh toán không?', 'a' => 'Có. Với đơn COD, bạn được đồng kiểm ngoại quan sản phẩm trước khi thanh toán.'],
            ['q' => 'Chính sách đổi trả như thế nào?', 'a' => 'Đổi trả trong 7 ngày với sản phẩm lỗi do nhà sản xuất, 1 đổi 1, còn nguyên hộp và phụ kiện.'],
            ['q' => 'Toàn Store có hỗ trợ trả góp không?', 'a' => 'Có. Hỗ trợ trả góp 0% lãi suất qua thẻ tín dụng, kỳ hạn 3–12 tháng.'],
            ['q' => 'Có những hình thức thanh toán nào?', 'a' => 'COD khi nhận hàng, chuyển khoản ngân hàng và thanh toán online qua ví MoMo.'],
            ['q' => 'Làm sao để được tư vấn cấu hình PC?', 'a' => 'Liên hệ hotline hoặc để lại thông tin ở trang Liên hệ, nhân viên kỹ thuật sẽ tư vấn miễn phí theo nhu cầu và ngân sách.'],
        ];

        return $this->render('page/faq.html.twig', [
            'faqs' => $faqs,
            'pages' => self::pages(),
            'current' => 'faq',
        ]);
    }
}
