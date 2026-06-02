# Bug report: Menu sidebar không highlight item đang active

## Mô tả

- **Phạm vi**: Toàn bộ Admin sidebar (`/admin/*`)
- **Triệu chứng**: Khi vào bất kỳ trang admin nào (Dashboard, Khách hàng, Bộ môn, Banner), menu item tương ứng không sáng lên (không có class `active`).
- **Steps to reproduce**: Đăng nhập Admin → vào bất kỳ trang nào → menu bên trái không có item nào được highlight.
- **Expected**: Menu item của trang hiện tại được highlight (class `active`).
- **Actual**: Tất cả menu items đều có trạng thái mặc định (không active).

## Phân loại

- Mức độ: trung bình (UX)
- Phạm vi ảnh hưởng: tất cả trang Admin

## Nguyên nhân gốc

Dashcode JS (`app.js`) dùng logic:
```js
var Href = window.location.href.split("/").pop(); // lấy segment cuối URL
$('a[href="' + Href + '"]').addClass("active");   // tìm theo href
```

Nhưng `href` của các menu item là **full URL** (từ `route()`), ví dụ `http://localhost/admin/programs`. JS lấy segment cuối `programs` rồi tìm `a[href="programs"]` → không match → không có item nào active.

Fix đúng: thêm class `active` **server-side** bằng Blade dựa trên `request()->routeIs(...)`.

## Cách fix

Thêm helper `@if(request()->routeIs('admin.<feature>.*'))` vào từng `<a>` trong sidebar để gán `active` theo route hiện tại.

## Files đã sửa

- `resources/views/admin/components/sidebar.blade.php` — thêm class `active` động theo route

## Verify

- [ ] Vào `/admin` (Dashboard) → item "Bảng điều khiển" sáng lên
- [ ] Vào `/admin/users` → item "Khách hàng" sáng lên
- [ ] Vào `/admin/programs` và `/admin/programs/1/edit` → item "Bộ môn" sáng lên
- [ ] Vào `/admin/banners` và `/admin/banners/create` → item "Banner" sáng lên
- [ ] `pint` pass (không có PHP file nào thay đổi)
