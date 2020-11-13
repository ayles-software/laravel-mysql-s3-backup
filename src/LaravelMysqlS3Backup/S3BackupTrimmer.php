<?php

namespace LaravelMysqlS3Backup;

use Carbon\Carbon;
use Aws\S3\S3Client;

class S3BackupTrimmer
{
    public $days;

    public $bucket;

    public $when;

    private function __construct($days, $bucket)
    {
        $this->days = $days;
        $this->bucket = $bucket;
        $this->when = now()->subDays($this->days)->startOfDay();
    }

    public static function make($days, $bucket)
    {
        return new static($days, $bucket);
    }

    public function run()
    {
        $s3 = new S3Client([
            'credentials' => [
                'key' => config('laravel-mysql-s3-backup.s3.key'),
                'secret' => config('laravel-mysql-s3-backup.s3.secret'),
            ],
            'endpoint' => config('laravel-mysql-s3-backup.s3.endpoint'),
            'region' => config('laravel-mysql-s3-backup.s3.region'),
            'version' => 'latest',
        ]);

        with($s3->listObjects([
            'Bucket' => $this->bucket,
        ]), function ($response) {
            return collect($response['Contents'] ?? [])->transform(function ($item) {
                return $item['Key'];
            });
        })->filter(function ($filename) {
            [$_, $date, $time] = explode('-', $filename);

            return (Carbon::createFromFormat('Ymd', $date))->lt($this->when);
        })->tap(function ($filenames) use ($s3) {
            if ($filenames->isNotEmpty()) {
                $s3->deleteObjects([
                    'Bucket' => $this->bucket,
                    'Delete' => [
                        'Objects' => $filenames->map(function ($filename) {
                            return ['Key' => $filename];
                        })->all(),
                    ],
                ]);
            }
        });
    }
}
