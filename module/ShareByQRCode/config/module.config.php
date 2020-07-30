<?php
return [
    'service_manager' => [
        'factories' => [
            \ShareByQRCode\V1\Rest\D\DResource::class => \ShareByQRCode\V1\Rest\D\DResourceFactory::class,
        ],
    ],
    'router' => [
        'routes' => [
            'share-by-qr-code.rest.d' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/d[/:qrcode]',
                    'defaults' => [
                        'controller' => 'ShareByQRCode\\V1\\Rest\\D\\Controller',
                    ],
                ],
            ],
        ],
    ],
    'api-tools-versioning' => [
        'uri' => [
            0 => 'share-by-qr-code.rest.d',
        ],
    ],
    'api-tools-rest' => [
        'ShareByQRCode\\V1\\Rest\\D\\Controller' => [
            'listener' => \ShareByQRCode\V1\Rest\D\DResource::class,
            'route_name' => 'share-by-qr-code.rest.d',
            'route_identifier_name' => 'qrcode',
            'collection_name' => 'd',
            'entity_http_methods' => [
                0 => 'GET',
            ],
            'collection_http_methods' => [
                0 => 'POST',
            ],
            'collection_query_whitelist' => [],
            'page_size' => 25,
            'page_size_param' => null,
            'entity_class' => \ShareByQRCode\V1\Rest\D\DEntity::class,
            'collection_class' => \ShareByQRCode\V1\Rest\D\DCollection::class,
            'service_name' => 'd',
        ],
    ],
    'api-tools-content-negotiation' => [
        'controllers' => [
            'ShareByQRCode\\V1\\Rest\\D\\Controller' => 'HalJson',
        ],
        'accept_whitelist' => [
            'ShareByQRCode\\V1\\Rest\\D\\Controller' => [
                0 => 'application/vnd.share-by-qr-code.v1+json',
                1 => 'application/hal+json',
                2 => 'application/json',
            ],
        ],
        'content_type_whitelist' => [
            'ShareByQRCode\\V1\\Rest\\D\\Controller' => [
                0 => 'application/vnd.share-by-qr-code.v1+json',
                1 => 'application/json',
            ],
        ],
    ],
    'api-tools-hal' => [
        'metadata_map' => [
            \ShareByQRCode\V1\Rest\D\DEntity::class => [
                'entity_identifier_name' => 'qrcode',
                'route_name' => 'share-by-qr-code.rest.d',
                'route_identifier_name' => 'qrcode',
                'hydrator' => \Laminas\Hydrator\ArraySerializable::class,
            ],
            \ShareByQRCode\V1\Rest\D\DCollection::class => [
                'entity_identifier_name' => 'qrcode',
                'route_name' => 'share-by-qr-code.rest.d',
                'route_identifier_name' => 'qrcode',
                'is_collection' => true,
            ],
        ],
    ],
    'api-tools-mvc-auth' => [
        'authorization' => [
            'ShareByQRCode\\V1\\Rest\\D\\Controller' => [
                'collection' => [
                    'GET' => false,
                    'POST' => true,
                    'PUT' => false,
                    'PATCH' => false,
                    'DELETE' => false,
                ],
                'entity' => [
                    'GET' => false,
                    'POST' => false,
                    'PUT' => false,
                    'PATCH' => false,
                    'DELETE' => false,
                ],
            ],
        ],
    ],
];
