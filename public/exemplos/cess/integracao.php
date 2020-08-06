<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

require_once "../../../vendor/autoload.php";

/*
Este é um exemplo de integração do projeto open source "share-by-code" com projeto CESS.
Para mais informações acesse: https://docs.vaultid.com.br/workspace/cess
 */

//Services URL
define("URL_SHAREQRCODE", "http://172.17.0.1:8085");
define("URL_CESS", "http://172.17.0.1:8080");

//Authorization Basic of the Share QR Code
define("AUTH_BASIC_USER", "meuusuario");
define("AUTH_BASIC_PW", "minhasenha");

define("FILE_NAME", "minha_assinatura2.pdf");
define("ACCESS_CODE", "senha123");

$isFinished  = false;
$currentStep = 0;

//Send request -> Create QR Code, sign file and get download link of signed file
if (!empty($_POST)) {

    $username = $_POST['username'];
    $otp      = $_POST['password'];

    //First step - Get QR Code (share-qrcode)
    $guzzle = new Client([
        'base_uri' => URL_SHAREQRCODE,
    ]);
    $headers = [
        'Content-type'  => 'application/json',
        'Accept'        => 'application/json',
        'Authorization' => 'Basic ' . base64_encode(AUTH_BASIC_USER . ':' . AUTH_BASIC_PW),
    ];

    $body = [
        'file'        => FILE_NAME,
        'access_code' => ACCESS_CODE,
    ];

    $qrcodeInfo = null;
    try {
        //See documentation (https://github.com/VaultID/share-by-qrcode#servi%C3%A7os--integra%C3%A7%C3%A3o)
        $request  = new Request('POST', '/d', $headers, json_encode($body));
        $response = $guzzle->send($request);

        $receivedBody = $response->getBody();
        $receivedBody = json_decode($receivedBody, true);

        //Stores the image in the data URL scheme (see RFC 2397, https://tools.ietf.org/html/rfc2397), because `gif` only returns base64 of the image
        $qrcodeInfo = [
            'image'       => "data:image/gif;base64," . $receivedBody['gif'],
            'access_code' => $receivedBody['access_code'],
            'id'          => $receivedBody['id'],
            'url'         => $receivedBody['url'],
        ];

    } catch (RequestException $ex) {
        echo sprintf("Failed to get QR Code. Code: %d - Body: %s", $ex->getResponse()->getStatusCode(), $ex->getResponse()->getBody());
        die;
    }

    //Second step - Sign file (cess)
    //See documentation of cess (https://docs.vaultid.com.br/workspace/cess/api/assinatura-de-documento-s)
    try {
        $rawBody = [
            "certificate_alias"     => "",
            "type"                  => "PDFSignature",
            "hash_algorithm"        => "SHA256",
            "auto_fix_document"     => true,
            "mode"                  => "sync",
            "signature_settings"    => [[
                "id"                  => "default",
                "contact"             => "123456789",
                "location"            => "Goiânia - GO",
                "reason"              => "Assinatura_Documento",
                "visible_sign_page"   => 1,
                "visible_sign_x"      => 0,
                "visible_sign_y"      => 0,
                "visible_sign_width"  => 300,
                "visible_sign_height" => 70,
                "visible_sign_img"    => $qrcodeInfo['image'],
            ]],
            "documents_source"      => "DATA_URL",
            "documents_destination" => "AWS_S3",
            "documents"             => [
                [
                    "id"                    => "0",
                    "signature_setting"     => "default",
                    "destination_file_name" => FILE_NAME,
                    "data"                  => "data:application/pdf;base64,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67Afo+Sv4X13QEuMizyKtAo2JyR4YRYgLecdbc/njoBR/2qnrJhm+Sl87CxiJ+d11UUuM/eJq8xN4hq5QW5yvkx8m/PId8glT3yf83XiU+YKZ7n+mqZKa/txS+XF++gUd4sWkzlt4Hf9zrqkwvMNl+Z/6AplbmRzdHJlYW0KZW5kb2JqCgo5IDAgb2JqCjw8L1R5cGUvRm9udC9TdWJ0eXBlL1RydWVUeXBlL0Jhc2VGb250L0JBQUFBQStMaWJlcmF0aW9uU2VyaWYKL0ZpcnN0Q2hhciAwCi9MYXN0Q2hhciA5Ci9XaWR0aHNbNzc3IDY2NiA2MTAgNjEwIDcyMiA2NjYgNjY2IDI1MCA2MTAgNTU2IF0KL0ZvbnREZXNjcmlwdG9yIDcgMCBSCi9Ub1VuaWNvZGUgOCAwIFIKPj4KZW5kb2JqCgoxMCAwIG9iago8PC9GMSA5IDAgUgo+PgplbmRvYmoKCjExIDAgb2JqCjw8L0ZvbnQgMTAgMCBSCi9Qcm9jU2V0Wy9QREYvVGV4dF0KPj4KZW5kb2JqCgoxIDAgb2JqCjw8L1R5cGUvUGFnZS9QYXJlbnQgNCAwIFIvUmVzb3VyY2VzIDExIDAgUi9NZWRpYUJveFswIDAgNTk1LjMwMzkzNzAwNzg3NCA4NDEuODg5NzYzNzc5NTI4XS9Hcm91cDw8L1MvVHJhbnNwYXJlbmN5L0NTL0RldmljZVJHQi9JIHRydWU+Pi9Db250ZW50cyAyIDAgUj4+CmVuZG9iagoKNCAwIG9iago8PC9UeXBlL1BhZ2VzCi9SZXNvdXJjZXMgMTEgMCBSCi9NZWRpYUJveFsgMCAwIDU5NSA4NDEgXQovS2lkc1sgMSAwIFIgXQovQ291bnQgMT4+CmVuZG9iagoKMTIgMCBvYmoKPDwvVHlwZS9DYXRhbG9nL1BhZ2VzIDQgMCBSCi9PcGVuQWN0aW9uWzEgMCBSIC9YWVogbnVsbCBudWxsIDBdCi9MYW5nKHB0LUJSKQo+PgplbmRvYmoKCjEzIDAgb2JqCjw8L0NyZWF0b3I8RkVGRjAwNTcwMDcyMDA2OTAwNzQwMDY1MDA3Mj4KL1Byb2R1Y2VyPEZFRkYwMDRDMDA2OTAwNjIwMDcyMDA2NTAwNEYwMDY2MDA2NjAwNjkwMDYzMDA2NTAwMjAwMDM2MDAyRTAwMzM+Ci9DcmVhdGlvbkRhdGUoRDoyMDIwMDUyMjEyMTc0My0wMycwMCcpPj4KZW5kb2JqCgp4cmVmCjAgMTQKMDAwMDAwMDAwMCA2NTUzNSBmIAowMDAwMDA2NzE2IDAwMDAwIG4gCjAwMDAwMDAwMTkgMDAwMDAgbiAKMDAwMDAwMDMyNCAwMDAwMCBuIAowMDAwMDA2ODg1IDAwMDAwIG4gCjAwMDAwMDAzNDQgMDAwMDAgbiAKMDAwMDAwNTg4OCAwMDAwMCBuIAowMDAwMDA1OTA5IDAwMDAwIG4gCjAwMDAwMDYxMDQgMDAwMDAgbiAKMDAwMDAwNjQzNCAwMDAwMCBuIAowMDAwMDA2NjI5IDAwMDAwIG4gCjAwMDAwMDY2NjEgMDAwMDAgbiAKMDAwMDAwNjk4NCAwMDAwMCBuIAowMDAwMDA3MDgxIDAwMDAwIG4gCnRyYWlsZXIKPDwvU2l6ZSAxNC9Sb290IDEyIDAgUgovSW5mbyAxMyAwIFIKL0lEIFsgPDA5QTAzQTk5RkVBMUQzMTgwNDEwN0NFNjM3Qzc3QzEyPgo8MDlBMDNBOTlGRUExRDMxODA0MTA3Q0U2MzdDNzdDMTI+IF0KL0RvY0NoZWNrc3VtIC8xNzA2OUFCQTE4QTY4QTg0NUVDMTY5ODkwOUQ4MDM5MQo+PgpzdGFydHhyZWYKNzI1NgolJUVPRgo=",
                ],
            ],
        ];

        $headers = [
            'Accept'        => 'application/json',
            'Content-type'  => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($username . ':' . $otp),
        ];

        $request  = new Request('POST', URL_CESS . '/signature-service', $headers, json_encode($rawBody));
        $response = $guzzle->send($request);

        $receivedBody = $response->getBody();
        $receivedBody = json_decode($receivedBody, true);

        if ($receivedBody['documents'][0]['status'] != 'SIGNED') {
            echo "Failed to sign file.";
            die;
        }

    } catch (RequestException $ex) {
        echo sprintf("Failed to sign file. Code: %d - Body: %s", $ex->getResponse()->getStatusCode(), $ex->getResponse()->getBody());
        die;
    }

    //Third step - Get download link (share-qrcode)
    try {
        //See documentation (https://github.com/VaultID/share-by-qrcode#download-do-arquivo)
        $url = $qrcodeInfo['url'] . '?_format=application/validador-iti+json&_secretCode=' . $qrcodeInfo['access_code']['value'] . '&_frontend=true';

        $response = $guzzle->get($url);

        $receivedBody = $response->getBody();
        $receivedBody = json_decode($receivedBody, true);

        if (!empty($receivedBody['download'])) {
            $qrcodeDownload = $receivedBody;
            $isFinished     = true;
        } else {
            echo "Failed to get download link";
            die;
        }
    } catch (RequestException $ex) {
        echo sprintf("Failed to get download link Code: %d - Body: %s", $ex->getResponse()->getStatusCode(), $ex->getResponse()->getBody());
        die;
    }

}

//Receive ID of QRCODE
if (!empty($_GET['id'])) {
    $code = $_GET['id'];

    try {
        $url = URL_SHAREQRCODE . '/d/' . $code .
            '?_format=application/validador-iti+json&_secretCode=' . ACCESS_CODE . '&_frontend=true';

        $guzzle       = new Client();
        $response     = $guzzle->get($url);
        $receivedBody = $response->getBody();
        $receivedBody = json_decode($receivedBody, true);

        if (!empty($receivedBody['download'])) {
            $qrcodeDownload = $receivedBody;
            $currentStep    = 1;
        } else {
            echo "Failed to get download link";
            die;
        }
    } catch (RequestException $ex) {
        echo sprintf("Failed to get download link. Code: %d - Body: %s", $ex->getResponse()->getStatusCode(), $ex->getResponse()->getBody());
        die;
    }
}

?>
<html>
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
        <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

        <title>Integração QR Code</title>
    </head>

    <body>
    <div class="app">

        <div class="container">
            <div class="row">

                <?php if ($currentStep === 0): ?>
                    <div class="col-6 py-2 " style="border: 1px solid #f3f3f3;">
                        <form name="form1" method="POST" action="/exemplos/cess/integracao.php" enctype="multipart/form-data">
                            <p class="title justify-content-center">Credenciais do usuário</p>

                            <div class="input-group mb-2">
                                <div class="input-group-prepend">
                                    <p class="input-group-text" label="username">Username</p>
                                </div>
                                <input type="text" class="form-control" placeholder="CPF/CPNJ" id="username" name="username">
                            </div>

                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <p class="input-group-text" label="password">OTP</p>
                                </div>
                                <input type="text" class="form-control" placeholder="OTP" id="password" name="password">
                            </div>

                            <button type="submit" class="btn btn-success form-control" >Assinar</button>
                        </form>
                    </div>

                    <div class="col-6 py-2" style="border: 1px solid #f3f3f3;">
                        <p class="title ">Resultado</p>
                        <?php if ($isFinished): ?>
                            <div class="col-12">
                                <p>QR Code gerado</p>
                                <img src="" id="qrcodeImage">
                                <p>URL: <?php echo $qrcodeInfo['url']; ?></p>
                            </div>

                            <div class="col-12">
                                <span>
                                    Link de download do arquivo
                                    <a href="<?php echo $qrcodeDownload['download']; ?>">Baixar aqui assinado</a>
                                </span>

                                <p><strong>
                                        Obs: Caso navegador não mostre QR Code e assinatura, baixe o mesmo e abre com Adobe Reader ou outro leitor de PDF.
                                </strong></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($currentStep === 1): ?>
                    <div class="col-12">
                        <p>Link de download do arquivo</p>

                        <a href="<?php echo $qrcodeDownload['download']; ?>">Baixar aqui assinado</a>

                        <strong>
                            Obs: Caso navegador não mostre QR Code e assinatura, baixe o mesmo e abre com Adobe Reader ou outro leitor de PDF.
                        </strong>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </div>

<script>
    var imgQRCODE = document.getElementById("qrcodeImage");
    imgQRCODE.src = "<?php echo $qrcodeInfo['image']; ?>";
</script>
</body>


<style>
    .title {
        font-size: 1.5rem;
        color: #335d65;
        display: flex;
        justify-content: center;
    }
</style>
</html>