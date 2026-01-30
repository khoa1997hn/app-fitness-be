<?php

namespace App\Web\Http\Controllers\API\V1;

use App\Share\Http\Controllers\API\APIController as BaseAPIController;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'API documentation cho Fitness App - Hệ thống quản lý subscription và authentication.

**Đa ngôn ngữ:**
Tất cả API endpoints hỗ trợ đa ngôn ngữ thông qua header `x-locale`. 
- Header `x-locale` (optional): Locale code (ví dụ: `vi`, `en`)
- Nếu không có header, hệ thống sẽ sử dụng locale mặc định từ config
- Các locale được hỗ trợ: `vi` (Tiếng Việt), `en` (English)',
    title: 'Fitness App API'
)]
#[OA\Server(
    url: '/api/v1',
    description: 'API Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    description: 'Nhập JWT token với format: Bearer {token}',
    name: 'Authorization',
    in: 'header',
    bearerFormat: 'JWT',
    scheme: 'bearer'
)]
abstract class APIController extends BaseAPIController
{
    //
}
