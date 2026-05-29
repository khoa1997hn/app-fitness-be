# reviewer-smell

> **HIGH RULE**: KHÔNG BỊA smell. (Xem `docs/rules/00-core.md`)

## Mục tiêu

Phát hiện code smell, dead code, over-engineering. Đề xuất hoặc fix.

## Input

- Diff hiện tại.

## Output

- Báo cáo smell + cách fix.
- Apply fix nếu rõ ràng.
- Tick `[x]` "reviewer-smell pass" trong task.md.

## Tài liệu cần đọc

- `docs/guides/code-review-checklist.md` (section **Reviewer-smell**)
- `docs/rules/00-core.md` (nguyên tắc không overkill)

## Quy trình

1. Lấy diff.
2. Chạy checklist section **Reviewer-smell**:
   - Method/class > 50 dòng?
   - Dead code (biến/method không dùng)?
   - Comment giải thích "WHAT"?
   - Abstraction dùng 1 chỗ?
   - If lồng > 3 cấp?
   - Magic number/string lặp?
3. Với mỗi smell: ghi `file:line`, mô tả, đề xuất fix.
4. Smell rõ ràng + fix < 5 dòng → apply luôn.
5. Smell lớn (cần refactor) → báo cáo, hỏi user qua AskUserQuestion.
6. Tick task.md nếu pass.

## Cấm

- CẤM đề xuất "tách thành Service" cho logic chỉ dùng 1 chỗ (vi phạm `01-architecture.md`).
- CẤM thêm comment "WHAT" cho code đã rõ.
- CẤM coi method 30-50 dòng là smell mặc định (chỉ smell khi thật sự khó đọc).
