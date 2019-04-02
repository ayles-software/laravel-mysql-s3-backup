<?php

namespace LaravelMysqlS3Backup\Commands;

use Aws\S3\S3Client;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MysqlS3Backup extends Command
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'db:backup';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a sqldump of your MySQL database and upload it to Amazon S3';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function handle()
	{
		// Build the command to be run
		$cmd = sprintf('mysqldump --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s',
			escapeshellarg(config('database.connections.mysql.host')),
			escapeshellarg(config('database.connections.mysql.username')),
			escapeshellarg(config('database.connections.mysql.password')),
			escapeshellarg(config('database.connections.mysql.database'))
		);

		$fileName = config('laravel-mysql-s3-backup.backup_dir') . '/' . config('laravel-mysql-s3-backup.filename');

		// Handle gzip
		if (config('laravel-mysql-s3-backup.gzip')) {
			$fileName .= '.gz';
			$cmd .= sprintf(' | gzip > %s', escapeshellarg($fileName));
		} else {
			$cmd .= sprintf(' > %s', escapeshellarg($fileName));
		}

		// Run the command
		$this->info('Running backup for database `' . config('database.connections.mysql.database') . '`');
		$this->info('Saving to ' . $fileName);
		$process = new Process($cmd);
		$process->run();

		if (! $process->isSuccessful()) {
			$this->error($process->getErrorOutput());
			return;
		}

		// Upload to S3
		if (config('laravel-mysql-s3-backup.s3')) {
			// Set the path structure and filename for S3
			$s3Filepath = sprintf('%s/%s/%s',
				date('Y'),
				date('m'),
				basename($fileName)
			);

			$s3 = S3Client::factory([
				'key' => config('laravel-mysql-s3-backup.s3.key'),
				'secret' => config('laravel-mysql-s3-backup.s3.secret'),
			]);

			$bucket = config('laravel-mysql-s3-backup.s3.bucket');
			$this->info(sprintf('Uploading to S3 - %s:%s', $bucket, $s3Filepath));

			$s3->putObject([
				'Bucket' => $bucket,
				'Key' => $s3Filepath,
				'SourceFile' => $fileName,
			]);
		}

		// Delete the local tmp file
		if (! config('laravel-mysql-s3-backup.keep_local_copy')) {
			$this->info("Deleting local backup file - {$fileName}");
			unlink($fileName);
		}

		$this->info('Done');
	}
}
