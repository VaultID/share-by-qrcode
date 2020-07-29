<?php
namespace ShareByQRCode\V1\Rest\D;

class DResourceFactory
{
    public function __invoke($services)
    {
        return new DResource();
    }
}
