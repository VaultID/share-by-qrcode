<?php
namespace ShareByQRCode\V1\Rest\D;

use Application\Adapter\Storage\StorageAdapterInterface;
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

        // Garantir que ainda não existe o ID do QRcode
        $qrcodeTentativas = 3;
        while( $qrcodeTentativas > 0 ) {
            // Sortear entre 90 mil possibilidades / segundo
            $qrcodeId = date('ymdHis') . rand(10000,99999);
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
                    $qrcodeTentativas = -1;
                }
            } catch( Exception $e ) {
            }
        }
        if( $qrcodeTentativas != -1 ) {
            return new ApiProblem(400, "Falha ao preparar QRCode. Tente novamente.");
        }

        return [
            'id' => $qrcodeId,
            'link' => $this->storageAdapter->getPublicLink(),
        ];
        
        return new ApiProblem(405, 'The POST method has not been defined');
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
        $this->storageAdapter->setId($id . '.json');
        return [
            'id' => $this->storageAdapter->getId(),
            'config' => $this->storageAdapter->getConfig(),
            'link' => $this->storageAdapter->getPublicLink(),
        ];
        return new ApiProblem(405, 'The GET method has not been defined for individual resources');
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
