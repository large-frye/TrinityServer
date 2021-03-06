<?php

namespace App\Util;

use App\Models\Photos;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use App\Models\ACL;
use Faker\Provider\Uuid;
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
    var $acl;

    const REGION = 'us-east-1';
    const PROFILE = 'default';
    const VERSION = '2006-03-01';
    const BUCKET = 'trinity-content';

    static $publicAcl;

    public function __construct()
    {
        $this->acl = new ACL('public-read', 'private');
        $this->createS3Client();
        Shared::$publicAcl = $this->acl->getPublic();
    }

    private static function createS3Client() {
        return S3Client::factory(array(
            'profile' => Shared::PROFILE,
            'region' => Shared::REGION,
            'version' => Shared::VERSION));
    }

    /**
     * Upload files to S3, should be rename S3Upload
     * @param $files
     * @param $input
     * @param $path
     * @param $name
     * @return array|string
     * @throws Exception
     */
    public function upload($files, $input, $path, $name = null) {
        $urls = [];
        $useFileName = false;

        if ($name == null)
            $useFileName = true;

        try {
            $this->files = $files;
            $this->input = $input;

            foreach($this->files as $file => $value) {
                $this->error = $this->file['error'];
                $name = $useFileName ? $value['name'] : $name;
                $key = $path . '/' . str_replace('/', '', $name);

                if ($value['error'] !== 0) {
                    return 'Error uploading file(s)';
                }

                try {
                    Shared::createS3Client()->putObject(array(
                        'ACL' => $this->acl->getPublic(),
                        'Bucket' => Shared::BUCKET,
                        'Key' => $key,
                        'SourceFile' => $value['tmp_name']
                    ));

                    $url = 'https://s3.amazonaws.com/trinity-content/' . $key;
                    $urls[$file] = $url;
                } catch (S3Exception $e) {
                    throw new Exception($e->getMessage());
                } catch (Exception $e) {
                    throw new Exception($e->getMessage());
                }
            }

            return $urls;
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function uploadLocalFile($file, $path, $name = null) {
        try {
            $name = $name === null ? $file : $name;
            $key = $path . '/' . str_replace('/', '', $name);
            Shared::createS3Client()->putObject(array(
                'ACL' => $this->acl->getPublic(),
                'Bucket' => Shared::BUCKET,
                'Key' => $key,
                'SourceFile' => $file
            ));

            $url = 'https://s3.amazonaws.com/trinity-content/' . $key;

            return $url;
        } catch (S3Exception $e) {
            throw new Exception($e->getMessage());
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
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

    /**
     * Create a zip from an array of files of Photos Model type, view the table in MySQL for reference.
     * @param array $files
     * @param string $dest
     * @param int $id
     * @return Object
     * @throws Exception
     */
    public static function createZip($files = array(), $dest = '', $id) {
        $key = 'inspections/' . $id . '/zipped/' . 'photos/' . date('Y-m-d') . '.zip';
        $shared = new Shared();
        $tmpFiles = [];

        try {
            $zip = new \ZipArchive();

            if (file_exists($dest)) {
                unlink($dest);
            }

            if ($zip->open($dest, \ZipArchive::CREATE) !== TRUE) {
                echo 'here';
                exit ("cannot open <$dest>\n");
            }

            foreach ($files as $file) {
                $info = new \SplFileInfo($file->file_url);
                $tmpFile = '/tmp/' . Uuid::uuid() . '.' . $info->getExtension();
                copy(str_replace(' ', '+', $file->file_url), $tmpFile);
                array_push($tmpFiles, $tmpFile);
            }

            foreach ($tmpFiles as $tmpFile) {
                $zip->addFile($tmpFile, ltrim($tmpFile, '/'));
            }

            $zip->close();

            Shared::cleanUpFiles($tmpFiles);

            // Put zip file to S3
            Shared::createS3Client()->putObject(array(
                'ACL' => $shared->acl->getPublic(),
                'Bucket' => Shared::BUCKET,
                'Key' => $key,
                'SourceFile' => $dest
            ));

            return $key;
        } catch (S3Exception $e) {
            throw new Exception($e->getMessage());
        } catch (\Exception $e) {
            throw new Exception('Random exception');
        }
    }

    public static function cleanUpFiles($files) {
        foreach ($files as $file) {
            unlink($file);
        }
    }

    public static function getExportPath($url, $fileName) {
      preg_match('/http:\/\/api.trinity.dev/', $url, $matches);
      if (count($matches) > 0) {
        return 'file:///Users/andrewfrye/Documents/php/TrinityServer/storage/exports/' . $fileName;
      } else {
        return 'http://52.205.216.249/exports/' . $fileName;
      }
    }
}