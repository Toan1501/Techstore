# 🛒 TECHSTORE — Website bán máy tính & linh kiện

Website thương mại điện tử (e-commerce) bán máy tính, laptop, PC gaming & linh kiện, xây dựng bằng **Symfony 6.4 (PHP 8.1+) + MySQL**, giao diện tiếng Việt phong cách GearVN.

![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4)
![Symfony](https://img.shields.io/badge/Symfony-6.4%20LTS-000000)
![MySQL](https://img.shields.io/badge/MySQL-8.x-4479a1)

---

## 🖼️ Giao diện

| Trang chủ | Chi tiết sản phẩm |
|:---:|:---:|
| [![Trang chủ](docs/screenshots/01-homepage.png)](docs/screenshots/01-homepage.png) | [![Chi tiết sản phẩm](docs/screenshots/02-product-detail.png)](docs/screenshots/02-product-detail.png) |

| Danh sách sản phẩm | Thanh toán | Tin tức |
|:---:|:---:|:---:|
| [![Danh sách](docs/screenshots/03-products.png)](docs/screenshots/03-products.png) | [![Thanh toán](docs/screenshots/04-checkout.png)](docs/screenshots/04-checkout.png) | [![Tin tức](docs/screenshots/05-news.png)](docs/screenshots/05-news.png) |

---

## ✨ Tính năng

**Người mua**
- Trang chủ: banner (quản lý được), Flash Sale đếm ngược, danh mục, sản phẩm theo danh mục, thương hiệu
- Sản phẩm: ảnh gallery + zoom, thông số kỹ thuật, đánh giá ⭐, hỏi đáp, sản phẩm liên quan, đã xem gần đây
- Tìm kiếm gợi ý, lọc theo danh mục / khoảng giá / thương hiệu / số sao, sắp xếp, phân trang
- Giỏ hàng (mini-cart), **yêu thích**, **so sánh sản phẩm**
- Thanh toán: địa chỉ VN (Tỉnh/Quận/Phường), phí ship tự tính, **mã giảm giá**
- Phương thức: **COD**, **chuyển khoản**, **Ví MoMo online**
- Tài khoản: đăng ký/đăng nhập, đơn hàng của tôi, chi tiết đơn + timeline trạng thái, hủy đơn

**Quản trị (admin)**
- Dashboard: thống kê + **biểu đồ doanh thu**
- Báo cáo bán hàng (bán chạy, doanh thu theo danh mục), **in hóa đơn**, **xuất CSV**
- Quản lý: sản phẩm, danh mục, đơn hàng (trạng thái + thanh toán), tin tức, banner, người dùng, đánh giá, hỏi đáp, mã giảm giá, tin nhắn liên hệ, cài đặt cửa hàng

**Kỹ thuật:** SEO (URL slug, JSON-LD, sitemap.xml, robots.txt, trang 404), migrations, seed dữ liệu mẫu, unit test.

---

## 📋 Yêu cầu

- **PHP >= 8.1** (khuyến nghị 8.2/8.3/8.4) — bật extension: `ctype`, `iconv`, `pdo_mysql`, `intl`, `mbstring`
- **Composer** 2.x
- **MySQL** 8.x (hoặc MariaDB 10.4+)
- Web server: **Laragon / Apache** (khuyến nghị) hoặc Symfony CLI

---

## 🚀 Cài đặt

### 1. Clone dự án từ GitHub
```bash
git clone https://github.com/Toan1501/Techstore.git
cd Techstore
```
> Với Laragon: clone vào thư mục `C:\laragon\www\Techstore`.

### 2. Cài đặt thư viện
```bash
composer install
```

### 3. Cấu hình môi trường
Tạo file **`.env.local`** ở thư mục gốc (không commit file này). Ví dụ dùng MySQL của Laragon (user `root`, không mật khẩu):
```dotenv
DATABASE_URL="mysql://root:@127.0.0.1:3306/techstore?serverVersion=8.4.0&charset=utf8mb4"

# (Tùy chọn) MoMo — mặc định đã dùng thông tin sandbox test công khai của MoMo.
# Chỉ cần điền khi bạn có tài khoản MoMo Business sandbox riêng:
# MOMO_PARTNER_CODE=
# MOMO_ACCESS_KEY=
# MOMO_SECRET_KEY=

# (Tùy chọn) Gửi email thật — mặc định null (không gửi)
# MAILER_DSN=smtp://user:pass@smtp.gmail.com:587
```
> Nếu MySQL của bạn có mật khẩu root, điền vào sau `root:`. Chỉnh `serverVersion` cho đúng phiên bản MySQL/MariaDB.

### 4. Tạo cơ sở dữ liệu & bảng
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 5. Nạp dữ liệu mẫu
```bash
php bin/console app:seed
```
Lệnh này tạo: 6 danh mục, 20 sản phẩm (ảnh/thông số/tồn kho/giảm giá), 6 tin tức, 3 banner, đơn hàng demo, 3 mã giảm giá, và 2 tài khoản.

### 6. Chạy website
- **Laragon/Apache:** truy cập `http://techstore.test/` (Laragon tự nhận `public/` làm document root; đã có sẵn `public/.htaccess`).
- **Hoặc Symfony CLI:** `symfony serve` rồi mở `http://127.0.0.1:8000`.
- **Hoặc PHP built-in:** `php -S 127.0.0.1:8000 -t public public/index.php`.

---

## 🔑 Tài khoản & dữ liệu demo

| Tài khoản | Mật khẩu | Vai trò |
|-----------|----------|---------|
| `admin` | `admin123` | Quản trị (trang `/admin`) |
| `customer` | `customer123` | Khách hàng |

**Mã giảm giá thử:** `GIAM10` (giảm 10%), `GIAM100K` (giảm 100.000₫, đơn từ 2 triệu), `SALE15` (giảm 15%, đơn từ 10 triệu).

**Ví test MoMo (sandbox):** dùng app **MoMo Test** (Android) hoặc trang QR sandbox. Thẻ ATIENT test (NCB): số thẻ `9704 0000 0000 0018` · tên `NGUYEN VAN A` · ngày `03/07` · OTP `OTP`. Xem hướng dẫn tại [developers.momo.vn](https://developers.momo.vn/v3/vi/docs/payment/onboarding/test-instructions).

---

## 🧪 Chạy test
```bash
php bin/phpunit
```

---

## ⚠️ Lưu ý

- **MySQL 8.4** đã bỏ plugin `mysql_native_password`. Hãy kết nối bằng tài khoản dùng `caching_sha2_password` (mặc định của `root`), hoặc chạy:
  ```sql
  ALTER USER 'user'@'localhost' IDENTIFIED WITH caching_sha2_password BY 'matkhau';
  ```
- **MoMo IPN** (webhook) cần URL công khai để MoMo gọi được; trên localhost dùng [ngrok](https://ngrok.com) để test. Đường dẫn `return` (trên trình duyệt) vẫn hoạt động bình thường.
- Nếu chưa cấu hình MoMo, checkout vẫn dùng được **COD / chuyển khoản** (MoMo tự vô hiệu).
- Cấu hình cửa hàng (hotline, số tài khoản, phí ship…) chỉnh trong **Admin → Cài đặt cửa hàng**.
- Chạy lại `php bin/console app:seed` để **reset** về dữ liệu mẫu ban đầu.

---

## 🗂️ Cấu trúc chính
```
src/Controller   — controllers (storefront + admin)
src/Entity       — entities (SanPham, Order, Review, News, Banner, Coupon, …)
src/Repository   — truy vấn dữ liệu
src/Service      — ShippingCalculator, VnpayService, OrderMailer, …
src/Form         — form types
src/Command      — app:seed (dữ liệu mẫu)
templates/       — giao diện (storefront + admin)
public/          — document root (index.php, css, uploads, .htaccess)
migrations/      — Doctrine migrations
```

---

Bản quyền mã nguồn thuộc về tác giả dự án. Được xây dựng với ❤️ bằng Symfony.
