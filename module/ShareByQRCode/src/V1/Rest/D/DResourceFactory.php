<?php
namespace ShareByQRCode\V1\Rest\D;

class DResourceFactory
{
    public function __invoke($services)
    {
        $config = $services->get('config');
        $storageAdapter = $services->get('storageAdapter');
        return new DResource($config, $storageAdapter);
    }
}
