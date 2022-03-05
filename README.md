# Laravel MySQL to S3 Backup

This is a very simple database backup script for Laravel. It takes a `mysqldump` and saves it to [Amazon S3](http://aws.amazon.com/s3/) or compatible object storage.
It also supports trimming backups to only have X days worth on S3.

This package is very opinionated. Other backup scripts can support other database types or other places besides S3 to store your backup. This does not.

## Installation

1. Install package

    ```
    composer require ayles-software/laravel-mysql-s3-backup
    ```
    Or add it to your `composer.json`:
    ```
    "ayles-software/laravel-mysql-s3-backup": "^4.0"
    ```

2. Update your composer packages

    ```bash
    $ composer update
    ```

3. Publish and edit the config

    ```bash
    $ php artisan vendor:publish --provider="LaravelMysqlS3Backup\ServiceProvider"
    ```

    Edit `config/laravel-mysql-s3-backup.php`:

    ```php
    's3' => [
        'key'    => 'AMAZON_API_KEY',
        'secret' => 'AMAZON_API_SECRET',
        'bucket' => 'your-bucket-name',
        'region' => 'your-bucket-region',
        'endpoint' => env('AWS_ENDPOINT'),
        'folder' => env('BACKUP_FOLDER'),
    ],
    ```

## Usage

```bash
$ php artisan db:backup
```

That's it. No arguments or optional parameters.

### Credit

This package was originally forked from [fitztrev](https://github.com/fitztrev/laravel-mysql-s3-backup) before a complete rewrite.

## License

Laravel MySQL to S3 Backup is open-sourced software licensed under the [MIT license](https://github.com/ayles-software/laravel-mysql-s3-backup/blob/master/LICENSE.md).
