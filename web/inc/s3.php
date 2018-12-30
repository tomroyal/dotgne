<?php 

// use s3
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
$s3 = S3Client::factory([
  'version' => '2006-03-01',
  'region' => getenv('AWS_REGION'),
  'credentials' => [
      'key'    => getenv('AWS_ACCESS_KEY_ID'),
      'secret' => getenv('AWS_SECRET_ACCESS_KEY')
  ]
]);

?>