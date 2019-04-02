<?php

namespace LaravelMysqlS3Backup;

use LaravelMysqlS3Backup\Commands\MysqlS3Backup;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('laravel-mysql-s3-backup.php'),
        ], 'config');
    }


    /**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->commands([
            MysqlS3Backup::class,
		]);
	}
}
