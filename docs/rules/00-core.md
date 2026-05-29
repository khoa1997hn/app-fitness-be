# Rule cốt lõi (HIGH)

Ba nguyên tắc bắt buộc cho MỌI tác vụ.

## 1. KHÔNG BỊA

- Cấm tự suy diễn nghiệp vụ, field, validation, status code, behavior… không có trong spec/yêu cầu.
- Cấm "đoán mò" tên model, endpoint, format response. Phải đọc code thực tế hoặc hỏi user.
- Nếu spec mơ hồ → DỪNG và dùng AskUserQuestion (xem `docs/guides/ask-protocol.md`).

## 2. ASK-FIRST

- Khi yêu cầu hoặc spec có khái niệm chưa rõ, có ≥ 2 cách hiểu, thiếu input → BẮT BUỘC hỏi trước khi code.
- Hỏi nhiều câu một lúc nếu cần. Hỏi sớm còn hơn code sai.
- Sau khi user trả lời, ghi lại quyết định vào spec/plan tương ứng.

## 3. KHÔNG OVERKILL

- Code đơn giản nhất hoạt động được. Không thêm abstraction "phòng khi cần".
- Không thêm interface/trait/service khi chỉ dùng 1 chỗ.
- Không thêm validation, error handling, fallback cho case không thể xảy ra.
- Đọc thêm `docs/rules/01-architecture.md`.
