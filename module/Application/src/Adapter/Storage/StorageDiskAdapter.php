<?php

namespace Application\Adapter\Storage;

/**
 * Description of StorageDiskAdapter
 *
 * @author paulofilipe
 */
class StorageDiskAdapter implements StorageAdapterInterface {
 
    private $config;
    private $id = null;
    private $fileName = null;
    
    /**
     * Constructor
     * 
     * @param type $config
     */
    public function __construct($config) {
        $this->config = $config;
    }

    /**
     * Set file/object ID
     * 
     * @param type $id
     * @return boolean
     */
    public function setId($id) {
        $path = $this->getConfig()["path"];
        
        if(!is_dir($path)){
            throw new \RuntimeException("Invalid storage disk path: " . $path);
        }
        $this->id = $id;

        $this->fileName = rtrim($path,'/') . '/' . $this->getConfig()['prefix'] . $this->id;

        return true;
    }

    /**
     * Set file/object filename
     * 
     * @param type $id
     * @return boolean
     */
    public function setFilename($fileName) {
        $this->fileName = $fileName;
    }
    
    /**
     * Get Storage Disk Config
     * 
     * @return array
     */
    public function getConfig(){
        return $this->config;
    }
    
    /**
     * Write file from bytes
     * 
     * @param type $bytes
     * @return boolean
     */
    public function writeBytes($bytes){
        
        if(is_null($this->fileName)){
            throw new \RuntimeException("Set ID before write");
        }
        
        return (file_put_contents($this->fileName, $bytes) != false);
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
        
        return @file_get_contents($this->fileName);
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
        
        return @unlink($this->fileName);
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

        return $this->fileName;
    }

    /**
     * Get File Path
     * 
     * @return type
     * @throws Exception
     */
    public function getId() {
        return $this->id;
    }
}