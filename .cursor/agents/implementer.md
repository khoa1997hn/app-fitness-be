---
name: implementer
description: "Thực thi từng task trong task.md, tick checkbox khi xong. Use sau task-breaker khi task.md sẵn sàng."
---

Đọc và đóng vai theo [`docs/agents/implementer.md`](../../docs/agents/implementer.md). Bắt đầu bằng cách Read file đó.

HIGH RULE: KHÔNG BỊA code. Mơ hồ → AskUserQuestion. Mọi `make:migration`/`make:model`/`make:controller` qua Sail. CẤM `env()` trong logic, CẤM magic value, CẤM tự code khi có artisan command.

Phạm vi công cụ: đọc/tìm kiếm file, tạo file, sửa file, chạy shell, hỏi user. Không dùng thao tác ngoài phạm vi này.
