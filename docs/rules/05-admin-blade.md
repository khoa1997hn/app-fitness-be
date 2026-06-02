# Admin Blade

## Ngôn ngữ

- TẤT CẢ label, message, button, validation text trong Admin **dùng tiếng Việt**.
- KHÔNG cần đa ngôn ngữ cho Admin.

## Template Dashcode

- HTML mẫu nằm trong `resources/dashcode/` — xây Blade theo mẫu này.
- CSS/JS/asset đã có sẵn ở `public/dashcode/`.
- Gọi asset qua helper: `asset('dashcode/css/...')`, `asset('dashcode/js/...')`.

## Tổ chức view

- Layout chung: `resources/views/admin/layouts/`
- Component tái dùng: `resources/views/admin/components/`
- Trang feature: `resources/views/admin/<feature>/index.blade.php`, `create.blade.php`, …

## CSS Dashcode là bản BIÊN DỊCH SẴN (static) — KHÔNG có JIT

- Admin nạp `dashcode/assets/css/app.css` (build sẵn), KHÔNG có Tailwind JIT. ⇒ **CHỈ dùng class CSS đã tồn tại** trong `app.css`.
- CẤM chế class Tailwind tùy ý (vd `bg-emerald-100`, `bg-sky-100`…) — không có trong build nên KHÔNG lên style (im lặng, không lỗi).
- Màu semantic có sẵn: `primary / secondary / success / warning / info / danger` (kèm `-500`, `text-<c>-500`, `bg-opacity-30`, `bg-<c>-100`). Nghi ngờ → grep trong `public/dashcode/assets/css/app.css` trước khi dùng.

## Hiển thị label enum/status trong danh sách — dùng badge màu

- Label enum/status ở **cột table / danh sách** KHÔNG để text trơn → render **badge bo tròn có màu** cho sinh động.
- Dùng partial chung: `@include('admin.components.enum-badge', ['enum' => $model->field])`.
  - Nhận enum instance (BenSampo) hoặc null; render `->description` (label từ `lang/<locale>/enums.php`).
  - Màu gán **ổn định theo value** từ palette **Dashcode** (`badge bg-<c>-500 text-<c>-500 bg-opacity-30 rounded-3xl`, `<c>` ∈ primary/success/warning/info/danger); null → text trung tính.
- KHÔNG tự viết span màu rời rạc ở mỗi view — tái dùng partial. KHÔNG dùng class màu ngoài palette Dashcode (xem mục trên).

## Hiển thị lỗi validate — DƯỚI từng field

- Lỗi validate hiển thị **ngay dưới field tương ứng**, KHÔNG gom 1 cục `@if($errors->any())` ở đầu form.
- Mỗi input: `@error('<field>')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror`.
- Field đa ngôn ngữ: dùng key theo locale, đặt dưới đúng input của locale đó — `@error('translations.'.$locale.'.name') … @enderror`.
- Field lồng (file/nested): `@error('video.file.path')`, `@error('translations.'.$locale.'.cover.path')`…

## Form đa ngôn ngữ — input các locale cạnh nhau theo từng field

Form create/edit có field dịch (translatable): **nhóm theo FIELD**, các locale của cùng 1 field xếp **cạnh nhau** (grid), KHÔNG tách thành block riêng theo từng locale.

```
Tên *
┌─────────────────────┬─────────────────────┐
│ Tiếng Việt *        │ Tiếng Anh           │
│ [input vi]          │ [input en]          │
└─────────────────────┴─────────────────────┘
```

- Lặp động theo `config('translatable.locales')`; required chỉ ép `config('translatable.fallback_locale')` (1 locale).
- Tên hiển thị locale lấy **động** qua `\Locale::getDisplayLanguage($locale, app()->getLocale())` (ext intl) — KHÔNG hardcode "Tiếng Việt"/"Tiếng Anh" (thêm locale mới tự có tên).
- Bố cục: `grid grid-cols-1 md:grid-cols-2 gap-4` (mỗi locale 1 cột), label field ở trên.

## Upload media — preview phản ánh NGAY sau khi upload

- Sau khi upload presigned thành công, phải hiển thị ngay media vừa chọn (ảnh `<img>` / video `<video>`), KHÔNG đợi save + reload.
- Thẻ preview LUÔN hiện diện trong DOM (ẩn `hidden` nếu chưa có), gắn `id`/`class` + `data-locale` để JS cập nhật.
- JS sau upload: set `src` = `URL.createObjectURL(file)`, bỏ `hidden`; với `<video>` gọi thêm `player.load()`.
- **Giữ preview qua validate fail:** hidden input (`path/name/...`) phải dùng `old()`; thẻ preview render `src` từ **`old('<prefix>.path')` trước** (dựng `App\Share\Attributes\File::fromArray(...)->url()` → presigned GET), fallback giá trị server. KHÔNG lấy `src` chỉ từ DB — sẽ mất ảnh/video vừa upload khi submit lỗi.

## Route

- File: `routes/admin.php`
- Prefix: `/admin`
- Name pattern: `admin.<feature>.<action>` (ví dụ `admin.users.index`).
