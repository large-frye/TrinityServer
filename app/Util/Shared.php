<?php

namespace App\Util;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use App\Models\ACL;
use League\Flysystem\Exception;

/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 5/29/16
 * Time: 9:46 PM
 */
class Shared
{
    var $client;
    var $input;
    var $files;
    var $file;
    var $error;

    const REGION = 'us-east-1';
    const PROFILE = 'default';
    const VERSION = '2006-03-01';
    const BUCKET = 'trinity-content';

    public function __construct()
    {
        $this->acl = new ACL('public-read', 'private');
        $this->createS3Client();
    }

    private function createS3Client() {
        $this->client = S3Client::factory(array(
            'profile' => Shared::PROFILE,
            'region' => Shared::REGION,
            'version' => Shared::VERSION));
    }

    public function upload($files, $input, $path) {
        try {
            $this->files = $files;
            $this->input = $input;
            $this->file = $this->files['file'];
            $this->error = $this->file['error'];
            $path .= '/' . $this->file['name'];

            if ($this->error !== 0) {
                return 'Error uploading file(s)';
            }

            try {
                $this->client->putObject(array(
                    'ACL' => $this->acl->getPublic(),
                    'Bucket' => Shared::BUCKET,
                    'Key' => $path,
                    'SourceFile' => $this->file['tmp_name']
                ));

                $url = 'https://s3.amazonaws.com/trinity-content/' . $path;
                return $url;
            } catch (S3Exception $e) {
                return response()->json(compact('e'), 200);
            }
        } catch (\Exception $e) {
            return response()->json(compact('e'), 200);
        }

    }
}