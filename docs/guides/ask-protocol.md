# ASK protocol

LLM PHẢI hỏi user khi gặp mơ hồ. Đây là rule HIGH (xem `docs/rules/00-core.md`).

## Khi nào BẮT BUỘC hỏi

1. Spec dùng khái niệm chưa định nghĩa rõ (ví dụ "trang chủ", "highlight" — highlight là gì?).
2. Có ≥ 2 cách hiểu hợp lý cho cùng một yêu cầu.
3. Thiếu input cụ thể: endpoint trả field nào, validation rule, status code, default value, sort order, pagination size…
4. Yêu cầu nghiệp vụ có ngoại lệ chưa nêu (ví dụ: "xóa user" → xóa cứng hay xóa mềm? Còn liên quan post của user thì sao?).
5. Path/file không rõ vị trí (ví dụ: "thêm vào file config" — file nào?).
6. UI mockup mơ hồ (ví dụ: "list banner đẹp" — sort theo gì, paginate ra sao?).

## Cách hỏi

- Dùng `AskUserQuestion` tool (Cursor và Claude Code đều có).
- Mỗi lần gọi: 1-4 câu, mỗi câu 2-4 option.
- Đề xuất option "(Khuyến nghị)" lên đầu nếu có hướng best practice rõ.
- Tránh hỏi câu yes/no đơn lẻ — đưa luôn 2-3 cách triển khai để user chọn.

## CẤM

- CẤM tự suy diễn rồi code, sau đó báo "tôi đã làm như này nếu sai user sửa".
- CẤM dùng "mặc định Laravel" làm lý do bỏ qua việc hỏi rule nghiệp vụ.
- CẤM hỏi "tôi có nên làm X không?" khi đáp án rõ ràng — chỉ hỏi khi thật sự mơ hồ.

## Sau khi user trả lời

- Ghi quyết định vào spec/plan tương ứng (section "Quyết định").
- Lần sau gặp case tương tự trong cùng spec → đọc lại quyết định, không hỏi lại.
