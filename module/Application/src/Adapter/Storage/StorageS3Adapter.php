<?php

namespace Application\Adapter\Storage;

use Aws\Sts\StsClient;
use Aws\S3\S3Client;
use Aws\S3\ObjectUploader;
use Aws\S3\Exception\S3Exception;

/**
 * Description of StorageS3Adapter
 *
 * @author paulofilipe
 */
class StorageS3Adapter implements StorageAdapterInterface {
 
    private $_config;
    private $_fileName = null;
    private $s3;
    
    /**
     * DI 
     * 
     * @param type $config
     */
    public function __construct($config) {
        $this->_config = $config;

        $this->s3 = new S3Client([
            'version' => $config['version'],
            'region'  => $config['region']
        ]);
    }
    
    /**
     * Get Storage Disk Config
     * 
     * @return array
     */
    public function getConfig(){
        return $this->_config;
    }

    /**
     * Set File Identity
     * 
     * @param type $checksum
     * @param type $uuid
     * @param type $extension
     * @return boolean
     */
    public function setIdentity($checksum, $uuid, $extension = '.bin'){
        $path = $this->getConfig()['path'];
        
        //Generate subpath from uuid (3-depth)
        // $maxDepth = 3;
        // $subpathFolders = explode("-", $uuid);
        // foreach($subpathFolders as $folder){
            
        //     if($maxDepth == 0) break;
            
        //     $path .= "/" . $folder;
        //     $maxDepth--;
        // }
        
        $this->_fileName = ltrim($path . "/" . $checksum . '-' . $uuid . $extension, "/");
    }

    public function getTempToken($username,$timetolive) {
        try {
            $configClient = array(
                'version' => $this->_config['version'],
                'region' =>  $this->_config['region'],
                //'credentials' => array(
                //    'key' => $config->s3->aws_access_key_id,
                //    'secret' => $config->s3->aws_secret_access_key
                //)
            );
            $sts = new StsClient($configClient);

            $path = $this->getConfig()['path'] . '/' . $username;

            $sessionToken = $sts->getFederationToken(array(
                'Name' => 'User' . $username,
                'DurationSeconds' => (int) $timetolive,
                'Policy' => json_encode(array(
                    'Statement' => array(
                        array(
                            'Sid' => 'randomstatementid1' . time(),
                            'Action' => array('s3:ListBucket'),
                            'Effect' => 'Allow',
                            'Resource' => 'arn:aws:s3:::' . $this->_config['bucket']
                        ),
                        array(
                            'Sid' => 'randomstatementid2' . time(),
                            'Action' => array('s3:DeleteObject','s3:GetObject'),
                            'Effect' => 'Allow',
                            'Resource' => 'arn:aws:s3:::' . $this->_config['bucket'] . "{$path}/*"
                        )
                    )
                ))
            ));

            if(!isset($sessionToken['Credentials']) || !is_array($sessionToken['Credentials'])){
                return false;
            }

            return [
                'Region' => $this->_config['region'],
                'Bucket' => $this->_config['bucket'],
                'Path' => $path,
                'Credentials' => $sessionToken['Credentials']
            ];
        } catch (\Exception $ex) {
            addLog("Falha ao gerar token temporário " . $ex);
            throw new \Exception("Falha ao gerar token temporário");
        }
    }
    
    /**
     * Write file from bytes (pointer var)
     * 
     * @param type $bytes
     * @return boolean
     */
    public function writeFromBytes(&$bytes){
        
        if(is_null($this->_fileName)){
            throw new \RuntimeException("Set Identity before write");
        }

        $tmpFile = "/tmp/s3-tmp" . sha1(rand(0,999) . time()) .  basename($this->_fileName);
        file_put_contents($tmpFile, $bytes);
        
        $uploader = new ObjectUploader(
            $this->s3,
            $this->_config['bucket'],
            $this->_fileName,
            fopen($tmpFile , 'rb')
         );
         unlink($tmpFile);

         $result = $uploader->upload();
         
         if ($result["@metadata"]["statusCode"] != '200') {
            //addLog("S3 Exception, " . print_r($result["@metadata"],1));
            return false;
         }

        return true;
    }

    public function setRetention($date) {
        $result = $this->s3->putObjectRetention([
            'Bucket' => $this->_config['bucket'], // REQUIRED
            'BypassGovernanceRetention' => true,
            //'ContentMD5' => '<string>',
            'Key' => $this->_fileName, // REQUIRED
            'RequestPayer' => 'requester',
            'Retention' => [
                'Mode' => 'GOVERNANCE',
                'RetainUntilDate' => $date,//<integer || string || DateTime>,
            ],
            //'VersionId' => '<string>',
        ]);
        return $result;
    }

    public function listVersions() {
        $pos = strrpos($this->_fileName,'/')+1;

        $result = $this->s3->listObjectVersions([
            'Bucket' => $this->_config['bucket'], // REQUIRED
            'Delimiter' => '/',
            'EncodingType' => 'url',
            //'KeyMarker' => substr($this->_fileName,$pos),
            //'MaxKeys' => <integer>,
            'Prefix' => $this->_fileName, //substr($this->_fileName,0,$pos),
            //'VersionIdMarker' => '<string>',
        ]);
        return [
            'Versions' => $result->get('Versions'),
            'DeleteMarkers' => $result->get('DeleteMarkers'),
        ];
    }
    
    /**
     * Write file from filename (full path)
     * 
     * @param type $filePath
     * @return boolean
     */
    public function writeFromFilePath($filePath){
        
        if(is_null($this->_fileName)){
            throw new \RuntimeException("Set Identity before write");
        }

        $uploader = new ObjectUploader(
            $this->s3,
            $this->_config['bucket'],
            $this->_fileName,
            fopen($filePath , 'rb')
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
        
        if(is_null($this->_fileName)){
            throw new \RuntimeException("Set Identity before read");
        }
        //echo $this->getPublicLink();die;
        return @file_get_contents($this->getPublicLink());
    }
    
    /**
     * Get File Path
     * 
     * @return type
     * @throws RuntimeException
     */
    public function readFilePath(){
        if(is_null($this->_fileName)){
            throw new \RuntimeException("Set Identity before read");
        }
        
        return $this->_fileName;
    }
    
    /**
     * Copy file to path
     * 
     * @param type $filePath
     * @return boolean
     */
    public function copyTo($filePath){
        
        if(is_null($this->_fileName)){
            throw new \RuntimeException("Set Identity before copy");
        }

        $uploader = new ObjectUploader(
            $this->s3,
            $this->_config['bucket'],
            $filePath,
            fopen($$this->_fileName , 'rb')
         );

         $result = $uploader->upload();
         if ($result["@metadata"]["statusCode"] != '200') {
            //addLog("S3 Exception, " . print_r($result["@metadata"],1));
            return false;
         }

        return true;
    }
    
    /**
     * Public function remove file
     * 
     * @return boolean
     */
    public function remove(){
        
        if(is_null($this->_fileName)){
            throw new \RuntimeException("Set Identity before remove");
        }
        
        //@TODO implement this

        return true;
    }

    /**
     * Generate a public link
     * 
     * @return string
     */
    public function getPublicLink(){

        if(is_null($this->_fileName)){
            throw new \RuntimeException("Set Identity before read");
        }

        $cmd = $this->s3->getCommand('GetObject', [
            'Bucket' => $this->_config['bucket'],
            'Key' => $this->_fileName
        ]);
        $request = $this->s3->createPresignedRequest($cmd, '+5 minutes');

        $presignedUrl = (string) $request->getUri();

        return $presignedUrl;
    }
}