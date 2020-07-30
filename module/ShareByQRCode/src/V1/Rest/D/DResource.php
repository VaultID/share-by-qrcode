<?php
namespace ShareByQRCode\V1\Rest\D;

use Application\Adapter\Storage\StorageAdapterInterface;
use Application\Helper\QRCodeHelper;
use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\Rest\AbstractResourceListener;
use Laminas\Http\Response;

class DResource extends AbstractResourceListener
{
    protected $config;
    protected $storageAdapter;

    public function __construct($config,StorageAdapterInterface $storageAdapter)
    {
        $this->config = $config;
        $this->storageAdapter = $storageAdapter;
    }

    /**
     * Create a resource
     * 
     * Criar um QRCode, o link público, salvar metadados (JSON)
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function create($data)
    {

        // Nome/Identificador do arquivo a ser compartilhado
        if( empty($data->file) ) {
            return new ApiProblem(400, "Invalid parameter 'file'");
        }
        // Metadados iniciais do QRCode
        $qrcodeJson = [
            "id" => null,
            "file" => $data->file,
        ];

        // Garantir que ainda não existe o ID do QRcode
        $qrcodeTentativas = 3;
        while( $qrcodeTentativas > 0 ) {
            // Sortear entre 90 mil possibilidades / segundo
            $qrcodeId = rand(100,999) . date('ymdHis') . rand(10,99);
            // Reduzir o ID
            $qrcodeId = base_convert($qrcodeId,10,32);
            // Substituir caracteres que podem confundir a digitação
            $qrcodeId = strtr($qrcodeId,[
                'i' => 'w',
                'l' => 'x',
                'o' => 'y',
                '1' => 'z'
            ]);
            $qrcodeTentativas--;
            try {
                $this->storageAdapter->setId($qrcodeId.'.json');
                $qrcodeData = $this->storageAdapter->readBytes();
                if( strlen($qrcodeData) == 0 ) {
                    $qrcodeJson['id'] = $qrcodeId;
                    $qrcodeTentativas = -1;
                }
            } catch( \Exception $e ) {
            }
        }
        if( $qrcodeJson['id'] == null ) {
            return new ApiProblem(500, "Falha ao preparar QRCode. Tente novamente.");
        }

        // Aplicar ID no nome do arquivo
        $qrcodeJson['file'] = strtr($qrcodeJson['file'],[
            '{ID}' => $qrcodeJson['id']
        ]);

        // Código de acesso (para autenticação do QRCode)
        if( empty($data->access_code) ) {
            // Código de autenticação para acesso ao QRCode
            $qrcodeAuth = ''.rand(10000,99999).rand(1000,9999);
            $qrcodeAuth = base_convert($qrcodeAuth,10,32);
            $qrcodeAuth = strtr($qrcodeAuth,[
                'i' => 'w',
                'l' => 'x',
                'o' => 'y',
                '1' => 'z'
            ]);
            $qrcodeAuth = strtoupper( $qrcodeAuth );
            $qrcodeJson['access_code'] = [
                'type' => 'internal',
                'value' => $qrcodeAuth
            ];
        } else {
            $qrcodeJson['access_code'] = [
                'type' => 'external',
                'value' => $data->access_code
            ];
        }

        // Salvar metadados extras enviados pela aplicação
        if( !empty($data->metadata) ) {
            $qrcodeJson['metadata'] = $data->metadata;
        }

        /**
         * Salvar dados do QRCode no arquivo JSON
         */
        if(!$this->storageAdapter->writeBytes( json_encode($qrcodeJson) )) {
            return new ApiProblem(500, "Falha ao salvar dados do QRCode. Tente novamente.");
        }

        /**
         * Acrescentar URL do QRCode, e imagem do QRCode
         */
        $qrcodeLink = $this->config['app']['qrcode-base-url'];
        $qrcodeLink = rtrim($qrcodeLink,'/') . '/' . $qrcodeId;
        $qrcodeJson['url'] = $qrcodeLink;

        /**
         * Gerar imagem
         */
        $qrHelper = QRCodeHelper::getMinimumQRCode($qrcodeJson['url'], QR_ERROR_CORRECT_LEVEL_Q);
        $qrcodeImage = $qrHelper->getGifBytes(4,8);
        $qrcodeJson['gif'] = base64_encode($qrcodeImage);

        return $qrcodeJson;
    }

    /**
     * Delete a resource
     *
     * @param  mixed $id
     * @return ApiProblem|mixed
     */
    public function delete($id)
    {
        return new ApiProblem(405, 'The DELETE method has not been defined for individual resources');
    }

    /**
     * Delete a collection, or members of a collection
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function deleteList($data)
    {
        return new ApiProblem(405, 'The DELETE method has not been defined for collections');
    }

    /**
     * Fetch a resource
     *
     * @param  mixed $id
     * @return ApiProblem|mixed
     */
    public function fetch($id)
    {

        /**
         * VERIFICAR SE É O FRONTEND AUTENTICADO
         * OU VALIDADOR DO ITI
         */
        if( $this->getEvent()->getRequest()->getQuery()->offsetGet('_format') !== null
            && $this->getEvent()->getRequest()->getQuery()->offsetGet('_format') == "application/validador-iti json"
        ) {
            /**
             * Verificar se metadados do QRcode existem
             */
            try {
                $this->storageAdapter->setId($id . '.json');
                $qrcodeData = json_decode($this->storageAdapter->readBytes(),true);
                if( !$qrcodeData || count($qrcodeData) == 0 ) {
                    return new ApiProblem(404, 'Not Found');
                }
            } catch( \Exception $e ) {
                return new ApiProblem(404, 'Not Found');
            }

            /**
             * Verificar código de acesso
             */
            $secret = null;
            if( $this->getEvent()->getRequest()->getQuery()->offsetGet('_secret') !== null ) {
                $secret = $this->getEvent()->getRequest()->getQuery()->offsetGet('_secret');
            }
            if( $this->getEvent()->getRequest()->getQuery()->offsetGet('_secretCode') !== null ) {
                $secret = $this->getEvent()->getRequest()->getQuery()->offsetGet('_secretCode');
                
            }
            // Se for Código de Acesso gerado internamente, aplicar regras de auxílio
            if( $secret !== null && $qrcodeData['access_code']['type']=='internal' ) {
                $secret = strtoupper($secret);
                $secret = strtr($secret,[
                    'O' => '0' // não há letra 'o' no auth, se digitar trocar por 0 (zero)
                ]);
            }
            // Verificar código de acesso
            if( $secret !== null && $secret == $qrcodeData['access_code']['value'] ) {
                // Identificador do objeto
                $this->storageAdapter->setFilename( $qrcodeData['file'] );

                // Link autenticado para download
                $downloadLink = $this->storageAdapter->getPublicLink();

                if( $this->getEvent()->getRequest()->getQuery()->offsetGet('_frontend') !== null
                    && $this->getEvent()->getRequest()->getQuery()->offsetGet('_frontend') == "true"
                ) {
                    /**
                     * Resposta para frontend
                     */
                    $responseData = $qrcodeData;
                    $responseData['download'] = $downloadLink;
                } else {
                    /**
                     * Preparar resposta no formato esperado pelo ITI
                     */
                    $responseData = [
                        'version' => '1.0.0',
                        'prescription' => (object) [
                            'signatureFiles' => [
                                (object) [
                                    'url' => $downloadLink
                                ]
                            ]
                        ]
                    ];
                }
        
                $response = new Response();
                $response->setStatusCode(Response::STATUS_CODE_200);
                $response->getHeaders()->addHeaders([
                    'Content-Type' => 'application/json',
                ]);
                $response->setContent(
                    json_encode($responseData)
                );
        
                return $response;
            } else {
                return new ApiProblem(401, 'Not Authorized');
            }
        }

        /**
         * Redirecionar para frontend que irá solicitar o código de autenticação
         */
        $redirect = $this->config['app']['redirect-base-url'];
        $redirect = rtrim($redirect,'/') . '/' . $id;

        $response = new Response();
        $response->setStatusCode(Response::STATUS_CODE_302);
        $response->getHeaders()->addHeaders([
            'Location: ' . $redirect
        ]);
        $response->setContent(
            '302 Found' . PHP_EOL
            . $redirect
        );

        return $response;
    }

    /**
     * Fetch all or a subset of resources
     *
     * @param  array $params
     * @return ApiProblem|mixed
     */
    public function fetchAll($params = [])
    {
        return new ApiProblem(405, 'The GET method has not been defined for collections');
    }

    /**
     * Patch (partial in-place update) a resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function patch($id, $data)
    {
        return new ApiProblem(405, 'The PATCH method has not been defined for individual resources');
    }

    /**
     * Patch (partial in-place update) a collection or members of a collection
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function patchList($data)
    {
        return new ApiProblem(405, 'The PATCH method has not been defined for collections');
    }

    /**
     * Replace a collection or members of a collection
     *
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function replaceList($data)
    {
        return new ApiProblem(405, 'The PUT method has not been defined for collections');
    }

    /**
     * Update a resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return ApiProblem|mixed
     */
    public function update($id, $data)
    {
        return new ApiProblem(405, 'The PUT method has not been defined for individual resources');
    }
}
