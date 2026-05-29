# Commit / push protocol

## CẤM tự ý commit / push

- KHÔNG `git add` + `git commit` + `git push` khi user chưa duyệt.
- Sau khi finalizer chạy xong → DỪNG, dùng `AskUserQuestion` hỏi:
  - Có muốn commit không?
  - Có muốn push không?
  - Message commit nên là gì? (đề xuất sẵn, để user duyệt)

## Khi user đồng ý commit

- Stage thẳng theo file cụ thể (`git add path/...`), KHÔNG `git add .` / `git add -A`.
- Loại file nhạy cảm (`.env`, credential…) khỏi staging.
- Commit message ngắn gọn (≤ 70 ký tự cho subject), focus "WHY" hơn "WHAT".
- Format: `<type>: <subject>` — type ∈ {feat, fix, refactor, docs, chore, test}.

## Khi user đồng ý push

- KHÔNG force push (`git push --force`) trừ khi user explicit yêu cầu.
- KHÔNG push lên `main`/`master` trực tiếp nếu repo có quy ước feature branch — hỏi user.

## Sau khi push

- Hỏi user có cần tạo PR không.
- Nếu có → dùng `gh pr create` với title + body do user duyệt.
