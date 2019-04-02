<?php

namespace LaravelMysqlS3Backup\Commands;

use Aws\S3\S3Client;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MysqlS3Backup extends Command {

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
		$db_name = config('database.connections.mysql.database');
		$db_host = config('database.connections.mysql.host');
		$db_user = config('database.connections.mysql.username');
		$db_pass = config('database.connections.mysql.password');

		// Build the command to be run
		$cmd = sprintf('mysqldump --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s',
			escapeshellarg($db_host),
			escapeshellarg($db_user),
			escapeshellarg($db_pass),
			escapeshellarg($db_name)
		);

		$filename = config('laravel-mysql-s3-backup.backup_dir') . '/' . config('laravel-mysql-s3-backup.filename');

		// Handle gzip
		if (config('laravel-mysql-s3-backup.gzip')) {
			$filename .= '.gz';
			$cmd .= sprintf(' | gzip > %s', escapeshellarg($filename));
		} else {
			$cmd .= sprintf(' > %s', escapeshellarg($filename));
		}

		// Run the command
		$this->info('Running backup for database `' . $db_name . '`');
		$this->info('Saving to ' . $filename);
		$process = new Process($cmd);
		$process->run();

		if (! $process->isSuccessful()) {
			$this->error($process->getErrorOutput());
			return;
		}

		// Upload to S3
		if (config('laravel-mysql-s3-backup.s3')) {
			// Set the path structure and filename for S3
			$s3_filepath = sprintf('%s/%s/%s/%s',
				date('Y'),
				date('m'),
				date('d'),
				basename($filename)
			);

			$s3 = S3Client::factory([
				'key' => config('laravel-mysql-s3-backup.s3.key'),
				'secret' => config('laravel-mysql-s3-backup.s3.secret'),
			]);

			$bucket = config('laravel-mysql-s3-backup.s3.bucket');
			$this->info(sprintf('Uploading to S3 - %s:%s', $bucket, $s3_filepath));

			$s3->putObject([
				'Bucket' => $bucket,
				'Key' => $s3_filepath,
				'SourceFile' => $filename,
			]);
		}

		// Delete the local tmp file
		if (! config('laravel-mysql-s3-backup.keep_local_copy')) {
			$this->info("Deleting local backup file - {$filename}");
			unlink($filename);
		}

		$this->info('Done');
	}

}
