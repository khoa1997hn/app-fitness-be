# Codex — Project entry

> Đây là file Codex luôn load. Tương đương `alwaysApply: true` của Cursor MDC.

## ĐỌC TRƯỚC TIÊN

[`docs/project-overview.md`](docs/project-overview.md) — sản phẩm là gì, nghiệp vụ, stack, module đã có vs CHƯA có.

## 3 nguyên tắc HIGH

1. **KHÔNG BỊA** — Cấm tự suy diễn nghiệp vụ/field/validation/behavior không có trong spec. Mơ hồ → hỏi.
2. **ASK-FIRST** — Khi có ≥ 2 cách hiểu hoặc thiếu input, BẮT BUỘC dùng `AskUserQuestion` trước khi code.
3. **KHÔNG OVERKILL** — Code đơn giản nhất chạy được. Không tạo abstraction phòng xa. Không thêm validation/fallback cho case không xảy ra.

Chi tiết: [`docs/rules/00-core.md`](docs/rules/00-core.md). Quy trình hỏi: [`docs/guides/ask-protocol.md`](docs/guides/ask-protocol.md).

## Bộ kit AI

Toàn bộ kit dùng chung với Cursor — source of truth ở `docs/`. Ánh xạ:

| Khu vực | Cursor | Codex |
|---|---|---|
| Core rule (always) | `.cursor/rules/000-core.mdc` | File này (`AGENTS.md`) |
| Rule theo folder | `.cursor/rules/*.mdc` (glob) | Nested `AGENTS.md` trong folder + `docs/rules/*.md` |
| Subagent | (prompt template) | `.Codex/agents/*.md` |
| Slash command | `.cursor/commands/*.md` | `.Codex/commands/*.md` |
| Rule chi tiết / guide / template | `docs/rules/`, `docs/guides/`, `docs/templates/` | (dùng chung) |

## Rule files (source of truth: `docs/rules/`)

| File | Phạm vi | Load khi |
|---|---|---|
| [`00-core.md`](docs/rules/00-core.md) | KHÔNG bịa / ASK / KHÔNG overkill | Luôn (đã tóm tắt phía trên) |
| [`01-architecture.md`](docs/rules/01-architecture.md) | Controller-first, không Repository | Đụng `app/**/*.php` |
| [`02-code-quality.md`](docs/rules/02-code-quality.md) | `query()`, `\Throwable`, domain exception, enum-as-string | Đụng `app/**/*.php` |
| [`03-project-structure.md`](docs/rules/03-project-structure.md) | Admin / Web / Share | Khi tạo file mới trong `app/` |
| [`04-api-response.md`](docs/rules/04-api-response.md) | Map field, không return full model, `ResponseAPI` | Đụng `app/Web/` hoặc Admin JSON |
| [`05-admin-blade.md`](docs/rules/05-admin-blade.md) | Blade + Dashcode + tiếng Việt | Đụng `app/Admin/`, `resources/views/admin/` |
| [`06-docker-sail.md`](docs/rules/06-docker-sail.md) | Sail commands + LocalStack S3 | Khi chạy bash command PHP/composer/artisan |
| [`07-seeders.md`](docs/rules/07-seeders.md) | DatabaseSeeder vs FakeDatabaseSeeder | Đụng `database/seeders/` |
| [`08-swagger.md`](docs/rules/08-swagger.md) | OpenAPI v10 PHP 8 Attributes, không schema mơ hồ | Đụng `app/Web/Http/Controllers/API/V1/` |
| [`09-magic-and-env.md`](docs/rules/09-magic-and-env.md) | Không magic / env qua config / prefix env / `.env`↔`.env.example` | Đụng `app/**/*.php`, `config/`, `.env*` |
| [`10-database-design.md`](docs/rules/10-database-design.md) | Field tối thiểu, không phòng xa | Đụng `database/migrations/`, `app/Share/Models/` |
| [`11-enum.md`](docs/rules/11-enum.md) | BenSampo, cast, PHPDoc, response không `->value` | Đụng `app/Share/Enums/`, `app/Share/Models/` |
| [`12-file-upload.md`](docs/rules/12-file-upload.md) | S3 presigned + FileType + FileCast + PHPDoc | Đụng `app/Share/Services/File/`, `Attributes/File.php`, `Casts/FileCast.php`, `config/app_file.php` |
| [`13-subscription-iap.md`](docs/rules/13-subscription-iap.md) | SubscriptionService chung + provider riêng, DB transaction + lock, log channel | Đụng `app/Share/Services/Subscription/`, listeners |
| [`14-translatable.md`](docs/rules/14-translatable.md) | Astrotomic 2-table pattern, đa ngôn ngữ cả content | Khi thiết kế DB / Model có content |

Khi làm task → tự xác định rule liên quan và đọc file tương ứng. Mơ hồ → hỏi user.

## Subagent (`.Codex/agents/`)

17 vai trò — mỗi file là 1 subagent. Spawn qua Agent tool khi cần.

| Agent | Vai trò |
|---|---|
| `spec-analyzer` | Đọc/tạo spec.md |
| `question-asker` | Quét spec, hỏi user qua AskUserQuestion |
| `solution-reviewer` | Review solution/architecture/DB user propose, suggest tối ưu qua ASK |
| `api-designer` | Đề xuất design endpoint (method/path/request/response) từ spec |
| `api-analyzer` | Liệt kê endpoint/migration/model ảnh hưởng (impact) |
| `planner` | Viết plan.md |
| `task-breaker` | Viết task.md checklist |
| `implementer` | Code theo task.md |
| `openapi-writer` | Viết/sửa Swagger annotation cho endpoint Web V1 |
| `reviewer-rules` | Review theo docs/rules/ |
| `reviewer-smell` | Review code smell / dead code |
| `reviewer-security` | Review bảo mật |
| `reviewer-duplicate` | Tìm + fix duplicate |
| `cleaner` | Dọn rác (file/biến/hàm/env/route/view không dùng) |
| `docs-syncer` | Update project-overview + rules + guides + agents khi state đổi |
| `bug-classifier` | Phân loại bug dễ/khó (chỉ /fix-bug) |
| `finalizer` | Migration + pint + summary |

Mỗi agent file (`.Codex/agents/<name>.md`) có YAML frontmatter (`name`, `description`, `tools`) và body chi tiết "Mục tiêu / Input / Output / Tài liệu cần đọc / Quy trình / Cấm".

## Slash command (`.Codex/commands/`)

| Command | Mục đích | Chuỗi vai trò |
|---|---|---|
| `/implement-spec` | Tạo feature mới | 16 vai trò |
| `/update-spec` | Sửa feature đã có | 16 vai trò |
| `/fix-bug` | Fix bug | 17 vai trò (nhánh KHÓ) hoặc 9 (nhánh DỄ) |

Đầy đủ trong [`docs/commands/`](docs/commands/).

## Ràng buộc nghiêm

- **LUÔN dùng Laravel Sail** cho MỌI lệnh PHP-related (php/artisan/composer/pint/phpunit/tinker...): `sail exec --user sail laravel.test <command>`. CẤM chạy trực tiếp ở host, CẤM chạy qua `docker`/`docker compose` thường. Xem [`docs/rules/06-docker-sail.md`](docs/rules/06-docker-sail.md).
- KHÔNG tự `git commit` / `git push`. Finalizer chỉ HỎI, user duyệt mới làm. Xem [`docs/guides/commit-protocol.md`](docs/guides/commit-protocol.md).
- KHÔNG skip vai trò trong workflow.
- KHÔNG bịa rule/nghiệp vụ — mọi quyết định phải dựa trên spec hoặc rule file.
