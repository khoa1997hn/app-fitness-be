# Command: /fix-bug

Fix bug đã có trong production / dev.

## RULE HIGH (bắt buộc tuyệt đối)

1. **TUYỆT ĐỐI KHÔNG BỊA** — Cấm tự suy diễn nguyên nhân bug, hành vi FE/client, field, endpoint, response… không có trong mô tả user, spec, hoặc bằng chứng reproduce thực tế.
2. **LUÔN HỎI TRƯỚC KHI FIX** — Bug report mơ hồ, thiếu steps reproduce, thiếu request/response lỗi, có ≥ 2 cách hiểu nguyên nhân → **BẮT BUỘC** `AskUserQuestion`. **CẤM** tự đoán nguyên nhân rồi sửa code.
3. **CẤM GIẢ ĐỊNH FE/CLIENT** — Không được tự cho rằng FE truyền sai field, sai URL, sai id… trừ khi user cung cấp bằng chứng hoặc xác nhận.
4. **CHƯA RÕ NGUYÊN NHÂN → CHƯA ĐƯỢC IMPLEMENT** — Kể cả nhánh DỄ: phải reproduce được hoặc user xác nhận root cause trước khi `implementer` sửa.
5. Chi tiết: [`docs/rules/00-core.md`](../rules/00-core.md), [`docs/guides/ask-protocol.md`](../guides/ask-protocol.md).

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
   - **docs-syncer**
   - **finalizer** (cập nhật `report.md` với "Cách fix" + "Files đã sửa")
3. Nếu **KHÓ**:
   - **spec-analyzer** (tạo spec.md trong folder bug)
   - **question-asker**
   - **solution-reviewer** *(skip nếu user chưa propose solution)*
   - **api-designer** *(skip nếu fix không đụng endpoint)*
   - **api-analyzer**
   - **planner** (tạo plan.md)
   - **task-breaker** (tạo task.md)
   - **implementer**
   - **openapi-writer** *(skip nếu không chạm Web V1)*
   - **reviewer-rules / smell / security / duplicate**
   - **cleaner**
   - **docs-syncer**
   - **finalizer** (cập nhật `report.md`)

## Ràng buộc

- Tuân thủ **RULE HIGH** ở đầu file — ưu tiên cao nhất.
- Cùng ràng buộc với `/implement-spec`.
- `report.md` phải được điền "Cách fix" + "Files đã sửa" + "Verify" trước khi finalizer hỏi user commit. "Nguyên nhân gốc" phải dựa trên bằng chứng reproduce hoặc user xác nhận — **không bịa**.
- Path slug: kebab-case, ngắn gọn, không dấu. Ví dụ: `login-redirect-loop-2026-05-29`.
