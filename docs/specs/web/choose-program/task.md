# Task: choose-program

- [x] Migration `subscription_program_selections`
- [x] Model `SubscriptionProgramSelection` + relations
- [x] `ProgramSelectionService`
- [x] `ProgramSelectionController` + routes
- [x] OpenAPI + generate
- [x] pint
- [x] Verify tinker / manual
- [x] docs-syncer: project-overview
- [ ] STOP — hỏi user commit/push

## Update 2026-05-29 — GET /programs/purchased

- [x] `getPurchased()` trong service
- [x] Controller `purchased()` + route
- [x] OpenAPI + pint
- [x] project-overview
- [ ] STOP — hỏi user commit/push

## Update 2026-05-29 — Figma

- [x] Enrich getPurchased fields + flags
- [x] SubscriptionCancelController + route
- [x] OpenAPI + messages + pint
- [ ] STOP — hỏi user commit/push

## Update 2026-05-30 — GET /subscriptions/me

- [ ] SubscriptionCancelController → SubscriptionController (đổi tên file + class)
- [ ] Thêm show() method + OpenAPI trong SubscriptionController
- [ ] ProgramSelectionService: thêm getSubscriptionInfo()
- [ ] Xóa ProgramSelectionController@purchased + OpenAPI
- [ ] Xóa route GET programs/purchased; thêm route GET subscriptions/me
- [ ] Xóa purchased_program_1/2.png
- [ ] pint + l5-swagger:generate
