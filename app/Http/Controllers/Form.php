<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 2/24/16
 * Time: 10:04 PM
 */

namespace App\Http\Controllers;

use App\Models\Inspection;
use Aws\S3\Exception\S3Exception;
use Illuminate\Http\Exception\PostTooLargeException;
use Laravel\Lumen\Routing\Controller as BaseController;
use League\Flysystem\Exception;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Workorder;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Input;
use App\Models\ACL;

class Form extends BaseController
{
    var $inspection;
    var $bucket;
    var $acl;

    public function __construct()
    {
        $this->inspection = new Inspection();
        $this->bucket = 'trinity_images';
        $this->acl = new ACL('public-read', 'private');
    }

    public function get()
    {
        return $this->inspection->getForm();
    }

    public function save()
    {
        return $this->inspection->saveForm();
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function uploadForm()
    {
        $client = S3Client::factory(array('profile' => 'default', 'region' => 'us-east-1', 'version' => '2006-03-01'));
        $file = $_FILES['file'];
        $error = $file['error'];
        $input = Input::all();
        $workorder_id = $input['workorder_id'];
        $key = $input['key'];

        if ($error) {
            return response()->json(['error' => 'error uploading file'], 500);
        }

        try {
            $client->putObject(array(
                'ACL' => $this->acl->getPublic(),
                'Bucket' => $this->bucket,
                'Key' => $file['name'],
                'SourceFile' => $file['tmp_name']
            ));
            $url = $client->getObjectUrl($this->bucket, $file['name']);

            // Attach form to inspection_meta
            $this->inspection->updateMeta($workorder_id, $key, $url);

            return response()->json(compact('url'));
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}