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
        $this->bucket = 'trinity-content';
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
        try  {
            $client = S3Client::factory(array('profile' => 'default', 'region' => 'us-east-1', 'version' => '2006-03-01'));
            $files = $_FILES;
            $input = Input::all();
            $file = $_FILES['file'];
            $error = $file['error'];

            if ($error !== 0) {
                return response()->json(['error' => 'error uploading file'], 500);
            }

            $workorder_id = $input['workorder_id'];
            $key = $input['key'];
            $path = 'inspections/' . $workorder_id . '/sketch/'. $file['name'];

            try {
                $client->putObject(array(
                    'ACL' => $this->acl->getPublic(),
                    'Bucket' => $this->bucket,
                    'Key' => $path,
                    'SourceFile' => $file['tmp_name']
                ));
                $url = $client->getObjectUrl($this->bucket, $file['name']);
                $url = 'https://s3.amazonaws.com/trinity-content/' . $path;

                // Attach form to inspection_meta
                $this->inspection->updateMeta($workorder_id, $key, $url);

                return response()->json(compact('url'));
            } catch (S3Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 200);
        }
    }
}