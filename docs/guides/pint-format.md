# Pint format

Sau khi hoàn thành task code, BẮT BUỘC chạy pint để format theo Laravel default.

## Lệnh

```bash
sail exec --user sail laravel.test vendor/bin/pint
```

## Khi nào chạy

- Bước cuối của finalizer (sau migration, trước khi hỏi commit).
- Sau khi reviewer-duplicate apply fix.

## Lưu ý

- Pint sẽ thay đổi file → check `git status` xem có file nào pint sửa không.
- Nếu pint fail (syntax error) → xem lỗi rồi sửa code, KHÔNG bypass pint.

## Verify

Sau khi chạy pint:

```bash
sail exec --user sail laravel.test vendor/bin/pint --test
```

Phải báo "Nothing to fix". Nếu vẫn còn → chạy lại lệnh format.
