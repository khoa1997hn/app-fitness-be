# Command: /fix-bug

Fix bug đã có trong production / dev.

## Khi nào dùng

- User báo bug + mô tả triệu chứng / steps to reproduce.
- KHÔNG dùng cho feature mới → `/implement-spec`.
- KHÔNG dùng cho update nghiệp vụ → `/update-spec`.

## Input

- Mô tả bug (triệu chứng, reproduce, mong đợi vs thực tế).
- Optional: path feature liên quan, log, stacktrace.

## Output

Thư mục `docs/specs/<big>/<detail>/bug-<slug>-<YYYY-MM-DD>/`.

Tùy phân loại của **bug-classifier**:

- **DỄ** → chỉ `report.md`.
- **KHÓ** → đủ `spec.md` + `plan.md` + `task.md` + `report.md`.

Cộng với code đã fix + review + format.

## Chuỗi vai trò

1. **bug-classifier** → `docs/agents/bug-classifier.md`
2. Nếu **DỄ**:
   - **implementer** (fix thẳng) → `docs/agents/implementer.md`
   - **openapi-writer** *(skip nếu không chạm Web V1)*
   - **reviewer-rules / smell / security / duplicate**
   - **cleaner**
   - **finalizer** (cập nhật `report.md` với "Cách fix" + "Files đã sửa")
3. Nếu **KHÓ**:
   - **spec-analyzer** (tạo spec.md trong folder bug)
   - **question-asker**
   - **api-analyzer**
   - **planner** (tạo plan.md)
   - **task-breaker** (tạo task.md)
   - **implementer**
   - **openapi-writer** *(skip nếu không chạm Web V1)*
   - **reviewer-rules / smell / security / duplicate**
   - **cleaner**
   - **finalizer** (cập nhật `report.md`)

## Ràng buộc

- Cùng ràng buộc với `/implement-spec`.
- `report.md` phải được điền "Cách fix" + "Files đã sửa" + "Verify" trước khi finalizer hỏi user commit.
- Path slug: kebab-case, ngắn gọn, không dấu. Ví dụ: `login-redirect-loop-2026-05-29`.
