<?php

namespace Application\Adapter\Storage;

/**
 * Description of StorageAdapterInterface
 * 
 * @author paulofilipe
 */
interface StorageAdapterInterface {
    
    /**
     * Set file/object ID
     * 
     * @param type $id
     * @return boolean
     */
    public function setId($id);

    /**
     * Write file from bytes
     * 
     * @param type $bytes
     * @return boolean
     */
    public function writeBytes($bytes);
    
    /**
     * Get file bytes
     * 
     * @return mixed
     */
    public function readBytes();
    
    /**
     * Get File Path
     * 
     * @return type
     * @throws Exception
     */
    public function getId();
    
    /**
     * Public function remove file
     * 
     * @return boolean
     */
    public function remove();

     /**
     * Generate a public link
     * 
     * @return string
     */
    public function getPublicLink();

    /**
     * Get Storage Config
     * 
     * @return array
     */
    public function getConfig();
}