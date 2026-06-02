<?php

namespace App\Share\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PutBucketCorsCommand extends Command
{
    protected $signature = 's3:put-cors';

    protected $description = 'Cấu hình CORS cho bucket S3 để browser upload được qua presigned PUT';

    public function handle(): int
    {
        $bucket = config('filesystems.disks.s3.bucket');

        if (empty($bucket)) {
            $this->error('Chưa cấu hình bucket S3 (filesystems.disks.s3.bucket).');

            return self::FAILURE;
        }

        try {
            // disk s3 dùng AwsS3V3Adapter → getClient() trả về AWS S3Client gốc.
            $client = Storage::disk('s3')->getClient();

            $client->putBucketCors([
                'Bucket' => $bucket,
                'CORSConfiguration' => [
                    'CORSRules' => [[
                        'AllowedHeaders' => ['*'],
                        'AllowedMethods' => ['PUT', 'GET', 'HEAD'],
                        'AllowedOrigins' => ['*'],
                        'ExposeHeaders' => ['ETag'],
                        'MaxAgeSeconds' => 3600,
                    ]],
                ],
            ]);
        } catch (\Throwable $e) {
            $this->error('Set CORS thất bại: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Đã set CORS cho bucket [{$bucket}] (origins: *, methods: PUT/GET/HEAD).");

        return self::SUCCESS;
    }
}
