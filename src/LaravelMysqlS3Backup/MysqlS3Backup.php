<?php

namespace LaravelMysqlS3Backup;

use Aws\S3\S3Client;
use Aws\S3\MultipartUploader;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Aws\Exception\MultipartUploadException;

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
        $cmd = sprintf('mysqldump --host=%s --user=%s --password=%s --single-transaction --routines --triggers',
            escapeshellarg(config('database.connections.mysql.host')),
            escapeshellarg(config('database.connections.mysql.username')),
            escapeshellarg(config('database.connections.mysql.password'))
        );

        if (config('laravel-mysql-s3-backup.custom_mysqldump_args')) {
            $cmd .= ' '.config('laravel-mysql-s3-backup.custom_mysqldump_args');
        }

        $cmd .= ' '.escapeshellarg(config('database.connections.mysql.database'));

        $fileName = config('laravel-mysql-s3-backup.backup_dir').'/'.sprintf(config('laravel-mysql-s3-backup.filename'), date('Ymd-His'));

        // Handle gzip
        if (config('laravel-mysql-s3-backup.gzip')) {
            $fileName .= '.gz';
            $cmd .= sprintf(' | gzip > %s', escapeshellarg($fileName));
        } else {
            $cmd .= sprintf(' > %s', escapeshellarg($fileName));
        }

        if ($this->output->isVerbose()) {
            $this->output->writeln('Running backup for database `'.config('database.connections.mysql.database').'`');
            $this->output->writeln('Saving to '.$fileName);
        }

        if ($this->output->isDebug()) {
            $this->output->writeln("Running command: {$cmd}");
        }

        $process = Process::fromShellCommandline($cmd);
        $process->setTimeout(config('laravel-mysql-s3-backup.sql_timout'));
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error($process->getErrorOutput());

            if ($this->output->isVerbose()) {
                $this->output->writeln(sprintf(
                    'Unable to dump database for %s with a file name of %. Error: %s',
                    now()->toDateString(),
                    $fileName,
                    $process->getErrorOutput()
                ));
            }

            return;
        }

        if ($this->output->isVerbose()) {
            $this->output->writeln("Backup saved to {$fileName}");
        }

        // Upload to S3
        $s3 = new S3Client([
            'credentials' => [
                'key' => config('laravel-mysql-s3-backup.s3.key'),
                'secret' => config('laravel-mysql-s3-backup.s3.secret'),
            ],
            'region' => config('laravel-mysql-s3-backup.s3.region'),
            'version' => 'latest',
        ]);

        $bucket = config('laravel-mysql-s3-backup.s3.bucket');

        if ($this->output->isVerbose()) {
            $this->output->writeln(sprintf('Uploading %s to S3/%s', basename($fileName), $bucket));
        }

        $uploader = new MultipartUploader($s3, $fileName, [
            'bucket' => $bucket,
            'key' => basename($fileName),
        ]);

        try {
            $uploader->upload();
        } catch (MultipartUploadException $e) {
            if ($this->output->isVerbose()) {
                $this->output->writeln(sprintf(
                    'Unable to upload "%s" backup to s3. Error: %s',
                    $fileName,
                    $e->getMessage()
                ));
            }
        }

        // Delete the local tmp file
        if (! config('laravel-mysql-s3-backup.keep_local_copy')) {
            if ($this->output->isVerbose()) {
                $this->output->writeln("Deleting local backup file {$fileName}");
            }

            unlink($fileName);
        }

        if ($this->output->isVerbose()) {
            $this->output->writeln("Backup {$fileName} successfully uploaded to s3");
        }

        if (config('laravel-mysql-s3-backup.rolling_backup_days')) {
            if ($this->output->isVerbose()) {
                $this->output->writeln("Trimming {$bucket} have have only ".config('laravel-mysql-s3-backup.rolling_backup_days').' days of backups');
            }

            S3BackupTrimmer::make(config('laravel-mysql-s3-backup.rolling_backup_days'), $bucket)->run();
        }
    }
}
