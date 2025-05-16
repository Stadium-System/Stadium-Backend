<?php

use Aws\S3\S3Client;
use Illuminate\Support\Facades\Route;

Route::get('/test-s3', function () {
    try {
        $s3 = new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        $result = $s3->listBuckets();
        echo "<h2>S3 Connection Test</h2>";
        echo "<p>Connection successful!</p>";
        echo "<h3>Your Buckets:</h3>";
        echo "<ul>";
        foreach ($result['Buckets'] as $bucket) {
            echo "<li>" . $bucket['Name'] . "</li>";
        }
        echo "</ul>";
        
        // Try to access the specific bucket we're using
        $bucketName = env('AWS_BUCKET');
        $objects = $s3->listObjects([
            'Bucket' => $bucketName
        ]);
        
        echo "<h3>Contents of $bucketName:</h3>";
        echo "<ul>";
        foreach ($objects['Contents'] ?? [] as $object) {
            echo "<li>" . $object['Key'] . " (size: " . $object['Size'] . " bytes)</li>";
        }
        echo "</ul>";
        
    } catch (\Exception $e) {
        echo "<h2>S3 Connection Error</h2>";
        echo "<p>Error: " . $e->getMessage() . "</p>";
        echo "<p>Line: " . $e->getLine() . "</p>";
        echo "<p>File: " . $e->getFile() . "</p>";
    }
});