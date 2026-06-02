# Bug report: Ambiguous column 'id' GET /api/v1/programs

## Mô tả

- **Endpoint**: `GET /api/v1/programs`
- **Triệu chứng**: 500 Internal Server Error khi user có ít nhất 1 program yêu thích.
- **Lỗi**: `SQLSTATE[23000]: Integrity constraint violation: 1052 Column 'id' in where clause is ambiguous`
- **SQL sinh ra**: `... where id not in (3, 4) order by program_translations.sort asc, id desc`
- **Steps to reproduce**: Đăng nhập user có favorite ít nhất 1 program → gọi `GET /api/v1/programs`.
- **Expected**: Trả danh sách programs, favorited trước, còn lại sau.
- **Actual**: 500 error.

## Phân loại

- Mức độ: critical
- Phạm vi ảnh hưởng: `GET /api/v1/programs` (màn Home)

## Nguyên nhân gốc

`withTranslation()` sinh LEFT JOIN với `program_translations`. Cả 2 bảng (`programs` và `program_translations`) đều có cột `id`. Khi `whereNotIn('id', ...)` và `orderByDesc('id')` không kèm table qualifier, MySQL không biết `id` của bảng nào → ambiguous.

```php
// ProgramController.php dòng 107-111
->orderByDesc('id');           // ❌ ambiguous
...
->whereNotIn('id', $favoritedProgramIds);  // ❌ ambiguous
```

## Cách fix

Thêm table prefix `programs.` vào cả 2 chỗ.

## Files đã sửa

- `app/Web/Http/Controllers/API/V1/ProgramController.php` — thêm `programs.` prefix vào `orderByDesc` và `whereNotIn`

## Verify

- [ ] Gọi `GET /api/v1/programs` khi user có favorite program → không còn 500
- [ ] Gọi `GET /api/v1/programs` khi user không có favorite program → vẫn hoạt động bình thường
- [ ] `pint` pass
