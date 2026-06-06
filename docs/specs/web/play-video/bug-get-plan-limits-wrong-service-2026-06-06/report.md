# Bug report: Video play gọi getPlanLimits trên sai service

## Mô tả

**Triệu chứng:** `POST /api/v1/videos/{video}/play` trả lỗi 500:
`Call to undefined method App\Share\Services\Program\ProgramSelectionService::getPlanLimits()`

**Steps reproduce:**
1. User đã đăng nhập, có subscription hợp lệ.
2. Gọi `POST /api/v1/videos/{id}/play`.
3. Nhận response lỗi trên (file `VideoPlayService.php` line 36).

**Mong đợi:** 200 với metadata video + `file.url` (hoặc 403 nếu không đủ quyền).

## Phân loại

- Mức độ: **cao** (endpoint play video không dùng được)
- Phạm vi: `VideoPlayService::streamGate`, Web V1 play video

## Nguyên nhân gốc

`getPlanLimits()` nằm trên `SubscriptionService` (đã chuyển từ `ProgramSelectionService` theo spec choose-program 2026-05-30). `VideoPlayService` vẫn inject `ProgramSelectionService` và gọi `getPlanLimits()` — method không tồn tại.

## Cách fix

Inject `SubscriptionService` thay `ProgramSelectionService` trong `VideoPlayService`; gọi `$this->subscriptionService->getPlanLimits($subscription->plan)`.

## Files đã sửa

- `app/Share/Services/Video/VideoPlayService.php` — đổi dependency + gọi đúng service

## Verify

- [ ] `POST /videos/{video}/play` user có subscription + quyền → 200 (không còn 500)
- [ ] User không subscription → 403 `no_active_subscription`
- [x] `pint` pass
