# Spec-driven workflow

Tổng quan 3 workflow chính. Chi tiết từng workflow xem `docs/commands/`.

## Triết lý

- Mọi feature/bug đều có spec → plan → task → code → review → finalize.
- Spec là source of truth. Code không khớp spec → fix code hoặc update spec, KHÔNG để lệch.
- Mỗi step do 1 "agent" (vai trò) thực hiện, có docs riêng trong `docs/agents/`.

## Layout thư mục spec

```
docs/specs/
└── <big-feature-title>/                # Ví dụ: banners, subscriptions, auth
    └── <detail-feature-title>/         # Ví dụ: list-banners-api, create-banner-form
        ├── spec.md                     # Mô tả nghiệp vụ
        ├── plan.md                     # Kế hoạch triển khai
        ├── task.md                     # Checklist task
        ├── image.png                   # (tùy chọn) mockup/diagram
        └── bug-<slug>-<YYYY-MM-DD>/   # Mỗi bug 1 thư mục con
            ├── report.md
            ├── spec.md                 # (chỉ khi bug khó)
            ├── plan.md                 # (chỉ khi bug khó)
            └── task.md                 # (chỉ khi bug khó)
```

Quy ước đặt tên: kebab-case, tiếng Anh không dấu, ngắn gọn.

## 3 workflow

| Command | Mục đích | Path tạo file |
|---|---|---|
| `/implement-spec` | Tạo feature mới | `docs/specs/<big>/<detail>/spec.md` (nếu chưa có) |
| `/update-spec` | Sửa lại spec đã có | Cùng path, append vào plan.md/task.md |
| `/fix-bug` | Fix bug | `docs/specs/<big>/<detail>/bug-<slug>-<date>/` |

## Chuỗi vai trò chuẩn

```
spec-analyzer
  → question-asker
    → api-analyzer
      → planner
        → task-breaker
          → implementer
            → reviewer-rules
              → reviewer-smell
                → reviewer-security
                  → reviewer-duplicate
                    → finalizer
                      → STOP (hỏi commit/push)
```

`/fix-bug` thêm `bug-classifier` ở đầu chuỗi.
