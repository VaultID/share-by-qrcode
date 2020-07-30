<?php

namespace Application\Adapter\Storage;

use Interop\Container\ContainerInterface;

/**
 * Description of StorageAdapterFactory
 *
 * @author paulofilipe
 */
class StorageAdapterFactory {
    
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @return object
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');
        $classname = $config['app']['storage-adapter']['name'];
        $optionsCfg = json_decode( $config['app']['storage-adapter']['config'], true ); // Fazer parse do JSON
        
        $classname = str_replace(substr(strrchr(__CLASS__, "\\"), 1), $classname, __CLASS__);
        return new $classname($optionsCfg);
    }
}