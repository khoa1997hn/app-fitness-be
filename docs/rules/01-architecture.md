# Kiến trúc Laravel

## Logic ở đâu

- **Controller-first**: viết logic CRUD/business thẳng trong Controller.
- **Service**: chỉ tạo khi logic dùng lại ở ≥ 2 chỗ HOẶC phức tạp (nhiều bước, nhiều dependency).
- **KHÔNG dùng Repository pattern**. Gọi Eloquent trực tiếp.
- Không tự ý tạo layer (Action, UseCase, Manager…) khi chưa có yêu cầu.

## Validation

- Form đơn giản → `$request->validate([...])` trong Controller.
- Form phức tạp hoặc dùng lại → `FormRequest`.
- Không tạo Validator class riêng.

## Khi nào nâng Service?

Tạo Service khi thỏa MỘT trong:
- Cùng logic dùng cả Admin lẫn Web.
- Logic > ~30 dòng và có nhánh điều kiện rõ ràng.
- Cần chia sẻ với queue/listener/command.

Nếu chưa thỏa → giữ trong Controller.

## Đặt Service ở đâu

- `app/Share/Services/<Domain>/<Name>Service.php`
- Chỉ chứa method liên quan tới domain đó.
