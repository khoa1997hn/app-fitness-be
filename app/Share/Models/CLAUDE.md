# Models — DB design + translatable + enum + file

## DB design (rule 10)
- Field tối thiểu, không phòng xa (`created_by`, `deleted_at`, `status` chỉ 1 trạng thái, `order`, `meta`, `slug`, `uuid` — chỉ khi spec yêu cầu).
- Type nhỏ nhất đủ dùng (`boolean()`, `unsignedSmallInteger()`, `decimal(p,s)` cho tiền).
- Nullable theo nghiệp vụ. Index chỉ cho cột query thực. FK kèm `onDelete()`.
- Naming: snake_case; FK `<singular>_id`; boolean `is_`/`has_`; datetime `_at`; date `_date`.

## Enum cast (rule 11)
- Mọi field enum BẮT BUỘC cast trong `casts()` + PHPDoc `@property <EnumClass> $field`.

## File field (rule 12)
- Migration: `jsonb`. Cast: `FileCast::class`. PHPDoc: `@property File $field` (`App\Share\Attributes\File`).

## Translatable — đa ngôn ngữ (rule 14)
Project đa ngôn ngữ CẢ content (vi / en). Khi thiết kế field, tự hỏi: "Tiếng Nhật field này có khác không?" → CÓ → table translation.

Pattern 2-table:
- Main `<entities>`: field dùng chung.
- `<entity>_translations`: field theo locale + `locale` index + unique `<singular>_id + locale` + `cascadeOnDelete()` + KHÔNG timestamps.
- Model chính: `implements TranslatableContract`, `use Translatable`, `$translatedAttributes`. PHPDoc đủ kể cả field translated.
- Model translation: chứa cast (File/enum/datetime), `$timestamps = false`.
- CẤM lưu JSON `{"vi":"x","en":"y"}` trong 1 cột.

Tham khảo: [`Banner`](Banner.php), [`BannerTranslation`](BannerTranslation.php).

Chi tiết: [`docs/rules/10-database-design.md`](../../../../docs/rules/10-database-design.md), [`11-enum.md`](../../../../docs/rules/11-enum.md), [`12-file-upload.md`](../../../../docs/rules/12-file-upload.md), [`14-translatable.md`](../../../../docs/rules/14-translatable.md).
