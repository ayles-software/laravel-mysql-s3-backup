<?php

return [

	/*
	 * Configure with your Amazon S3 credentials
	 * You should use an IAM user who only has PutObject access
	 * to a specified bucket
	 *
	 * Or alternatively, you can set `s3` to false
	 * to disable S3 uploading entirely
	 */
	's3' => [
		'key'    => env('AWS_API_KEY'),
		'secret' => env('AWS_API_SECRET'),
		'bucket' => env('AWS_S3_BUCKET'),
	],
	// 's3' => false,

	/*
	 * Whether or not to gzip the .sql file
	 */
	'gzip'  => true,

	/*
	 * Backup filename
	 */
	'filename' => 'backup-%s.sql',

	/*
	 * Where to store the backup file locally
	 */
	'backup_dir' => '/tmp',

	/*
	 * Do you want to keep a copy of it or delete it
	 * after it's been uploaded?
	 */
	'keep_local_copy' => false,

];
