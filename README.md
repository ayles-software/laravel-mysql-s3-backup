# Laravel MySQL to S3 Backup

This is a very simple database backup script for Laravel. It takes a `mysqldump` and optionally saves it to [Amazon S3](http://aws.amazon.com/s3/).

This package is very opinionated. Other backup scripts can support other database types or other places besides S3 to store your backup. This does not.

## Installation

1. Install package

    ```composer require parkourben99/laravel-mysql-s3-backup```
      
    Or add it to your `composer.json`:
    ```
    "parkourben99/laravel-mysql-s3-backup": "1.0"
    ```

2. Update your composer packages

    ```bash
    $ composer update
    ```

3. Update `config/app.php`:

   Service Provider is autoloaded if you want to registered it manually then add

    ```php
    'providers' => array(
        ...
        'LaravelMysqlS3Backup\ServiceProvider',
    ),
    ```

4. Publish and edit the config

    ```bash
    $ php artisan config:publish parkourben99/laravel-mysql-s3-backup
    ```

    Edit `config/laravel-mysql-s3-backup.php`:

    ```php
    's3' => [
        'key'    => 'AMAZON_API_KEY',
        'secret' => 'AMAZON_API_SECRET',
        'bucket' => 'your-bucket-name',
    ],
    ```

## Usage

```bash
$ php artisan db:backup
```

That's it. No arguments or optional parameters.
