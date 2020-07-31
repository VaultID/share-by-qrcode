<?php

namespace Application\Adapter\Storage;

use Aws\Sts\StsClient;
use Aws\S3\S3Client;
use Aws\S3\ObjectUploader;
use Aws\S3\Exception\S3Exception;

/**
 * Description of StorageAdapterAwsS3
 *
 * @author paulofilipe
 */
class StorageAdapterAwsS3 extends StorageDiskAdapter {
 
    private $s3;
    
    /**
     * DI 
     * 
     * @param type $config
     */
    public function __construct($config) {

        // TTL padrão: 15 minutos (900)
        if( $config['ttl'] <= 0 ) {
            $config['ttl'] = 900;
        }

        $this->config = $config;

        //print_r($config); die;

        $this->s3 = new S3Client([
            'version' => '2006-03-01',
            'region'  => $config['region'],
            'credentials' => [
                'key' => $config['aws_access_key_id'],
                'secret' => $config['aws_secret_access_key'],
            ],
        ]);
    }

    /**
     * Set file/object ID
     * 
     * @param type $id
     * @return boolean
     */
    public function setId($id) {
        $this->id = $id;
        $this->fileName = $this->getConfig()['prefix'] . $this->id;
        return true;
    }

    // public function setFilename($fileName) - herdada

    // public function getConfig() - herdada

    /**
     * Write file bytes
     * 
     * @param type $bytes
     * @return boolean
     */
    public function writeBytes($bytes){
        
        if(is_null($this->fileName)){
            throw new \RuntimeException("Set Identity before write");
        }

        $uploader = new ObjectUploader(
            $this->s3,
            $this->config['bucket'],
            $this->fileName,
            $bytes
         );

         $result = $uploader->upload();
         
         if ($result["@metadata"]["statusCode"] != '200') {
            //addLog("S3 Exception, " . print_r($result["@metadata"],1));
            return false;
         }

        return true;
    }

    /**
     * Get file bytes
     * 
     * @return mixed
     */
    public function readBytes(){
        
        if(is_null($this->fileName)){
            throw new \RuntimeException("Set Identity before read");
        }
        
        try {
            // Get the object.
            $result = $this->s3->getObject([
                'Bucket' => $this->getConfig()['bucket'],
                'Key'    => $this->fileName
            ]);
        } catch (S3Exception $e) {
            throw new \Exception( $e->getMessage(), 404);
        }
    
        return ($result['Body']);    
    }

    /**
     * Public function remove file
     * 
     * @return boolean
     */
    public function remove(){
        
        if(is_null($this->fileName)){
            throw new \RuntimeException("Set Identity before remove");
        }
        
        //return @unlink($this->fileName);
        return false; // @TODO
    }

    /**
     * Generate a public link
     * 
     * @return string
     */
    public function getPublicLink(){

        if(is_null($this->fileName)){
            throw new \RuntimeException("Set Id before read");
        }

        $versoes = $this->listVersions();
        if( $versoes['Versions']==false || $versoes['DeleteMarkers'] ) {
            throw new \Exception("File not found or marked for deletion", 404);
        }

        $cmd = $this->s3->getCommand('GetObject', [
            'Bucket' => $this->config['bucket'],
            'Key' => $this->fileName
        ]);
        $request = $this->s3->createPresignedRequest($cmd, '+' . $this->getConfig()['ttl'] . ' seconds');

        $presignedUrl = (string) $request->getUri();

        return $presignedUrl;
        
    }

    // public function getId() - herdada

    /**
     * Consultar versões do arquivo
     */
    public function listVersions() {
        $pos = strrpos($this->fileName,'/')+1;

        $result = $this->s3->listObjectVersions([
            'Bucket' => $this->config['bucket'], // REQUIRED
            'Delimiter' => '/',
            'EncodingType' => 'url',
            //'KeyMarker' => substr($this->_fileName,$pos),
            //'MaxKeys' => <integer>,
            'Prefix' => $this->fileName, //substr($this->_fileName,0,$pos),
            //'VersionIdMarker' => '<string>',
        ]);
        return [
            'Versions' => $result->get('Versions'),
            'DeleteMarkers' => $result->get('DeleteMarkers'),
        ];
    }
    
}