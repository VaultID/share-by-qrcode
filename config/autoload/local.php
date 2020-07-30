<?php

$storageAdapterName = getenv('storageAdapterName') !== false ? getenv('storageAdapterName') : 'StorageDiskAdapter';
$storageAdapterConfig = getenv('storageAdapterConfig') !== false ? base64_decode( getenv('storageAdapterConfig') ) : '{"path":"/var/www/data/files","prefix":"qrcode-"}';


return [
    'app' => [
        'storage-adapter' => [
            'name' => $storageAdapterName,
            'config' => $storageAdapterConfig,
        ],
    ],
    'api-tools-mvc-auth' => [
        'authentication' => [
            'adapters' => [
                'basic' => [
                    'adapter' => \Laminas\ApiTools\MvcAuth\Authentication\HttpAdapter::class,
                    'options' => [
                        'accept_schemes' => [
                            0 => 'basic',
                        ],
                        'realm' => 'Share by QRCode',
                        'htpasswd' => 'data/authentication.htpasswd',
                    ],
                ],
            ],
        ],
    ],
];
