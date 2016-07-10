<?php

namespace App\Util;

use App\Models\Photos;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use App\Models\ACL;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;
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

    private static function createS3Client() {
        return S3Client::factory(array(
            'profile' => Shared::PROFILE,
            'region' => Shared::REGION,
            'version' => Shared::VERSION));
    }

    public function upload($files, $input, $path) {
        $urls = [];

        try {
            $this->files = $files;
            $this->input = $input;
            $key = '';

            foreach($this->files as $file => $value) {
                $this->file = $this->files[$file];
                $this->error = $this->file['error'];
                $key = $path . '/' . str_replace('/', '', $this->file['name']);

                if ($this->error !== 0) {
                    return 'Error uploading file(s)';
                }

                try {
                    Shared::createS3Client()->putObject(array(
                        'ACL' => $this->acl->getPublic(),
                        'Bucket' => Shared::BUCKET,
                        'Key' => $key,
                        'SourceFile' => $this->file['tmp_name']
                    ));

                    $url = 'https://s3.amazonaws.com/trinity-content/' . $key;
                    $urls[$file] = $url;
                } catch (S3Exception $e) {
                    return response()->json(compact('e'), 200);
                } catch (Exception $e) {
                    return response()->json(compact('e'), 200);
                }
            }

            return $urls;
        } catch (\Exception $e) {
            return response()->json(compact('e'), 200);
        }
    }

    public static function remove($key) {
        try {
            Shared::createS3Client()->deleteObject(array(
                'Bucket' => Shared::BUCKET,
                'Key' => $key
            ));
        } catch (S3Exception $e) {
            Log::error($e->getMessage());
        }
    }
}