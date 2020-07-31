# Share by QRcode Service

## Objetivo
Permitir que arquivos armazenados em áreas privadas sejam compartilhados através de um link e QRCode com autenticação simples ("Código de Acesso").

Pode ser utilizado como um serviço independente (um container Docker), ou incorporado ao código do seu projeto.

Se tiver sugestões, correções, melhorias, basta fazer um Pull Request ou abrir uma Issue!

Utiliza o **QRCode for PHP** de **Kazuhiko Arase**: https://github.com/kazuhikoarase/qrcode-generator

O termo "QR Code" é marca registrada de DENSO WAVE INCORPORATED: http://www.denso-wave.com/qrcode/faqpatent-e.html

---------------------

## Funcionamento

### Gerar QRCode/Link
Sua aplicação deve fazer uma chamada informando o nome/identificador do arquivo que deseja compartilhar. A resposta será um QRCode com uma URL única e um código de acesso.

Compartilhe então o QRCode e/ou a URL, e do Código de Acesso.

### Ler QRCode/Abrir Link
Se algum usuário acessar o link diretamente (que aponta para esse serviço), será redirecionado para uma URL definida nas configurações. O ID único será acrescentado ao final dessa URL de redirecionamento.

Isso é necessário para apresentar uma interface/frontend onde o usuário poderá digitar o Código de Acesso.

*Sugestão: criar neste projeto uma interface genérica para isso.*

### Acessar arquivo
Para acessar o arquivo, faça uma requisição para a mesma URL do QRCode acrescentando alguns parâmetros, dentre eles o Código de Acesso.

A resposta terá o link para download do arquivo.

### Padrão ITI/CFM/CFF
Esse serviço de compartilhamento implementa a versão 1.0.0 do padrão definido para Prescrição Eletrônica: https://assinaturadigital.iti.gov.br/duvidas/#1587761771301-8f0416f4-c42c

---------------------

## Configuração como serviço

Crie o arquivo `docker-compose.override.yml` para ajustar as configurações, ou edite o `docker-compose.yml`.

Escalabidade: é um serviço *stateless* que depende apenas do acesso ao repositório de arquivos. Pode ser escalado facilmente com um balanceador de carga ou DNS Round-robin.

### Requisitos

- Domínio ou subdomínio decicado para o serviço
- Certificado SSL
- Configuração do repositório dos arquivos, com permissão de escrita no local/pasta onde serão salvos os dados de cada QRCode (arquivos JSON)

### Configuração SSL

Coloque na pasta `cert/` três arquivos:
- `cert.pem` : arquivo do certificado SSL do site (formato PEM)
- `cert.key` : chave privada do certificado (formato PEM, sem senha)
- `AC.pem` : arquivo com cadeias intermediários (formato PEM)

*Se for utilizar um proxy reverso que terá o HTTPS pode ignorar essa parte, mas mantenha os arquivos atuais.*

### URL's

- `qrcodeBaseUrl` : Base-URL para o link do QRCode. O `ID` do QRCode será acrescentado a essa Base-URL. **Importante:** mantenha o `/d` no final da URL, a menos que modifique a rota do serviço. Exemplo: `https://seusite.com.br/d`
- `redirectBaseUrl` : Base-URL do frontend que irá solicitar o Código de Acesso (interface para o usuário). O `ID` do QRCode será acrescentado a essa Base-URL. Exemplo: `https://seusite.com.br/acessar/arquivo`

### Repositório de arquivos

A variável de ambiente `storageAdapterName` especifica o tipo de repositório que será usado. O mesmo repositório é usado para salvar os dados do QRCode (arquivo JSON) e para acessar os arquivos a serem compartilhados.

Cada tipo de repositório tem configurações diferentes em formato JSON. Codifique o JSON de configuração em Base64 e coloque na variável de ambiente `storageAdapterConfig`.

#### `StorageAdapterAwsS3` (AWS S3)

Salva os metadados em um bucket do **AWS S3**. Recomendamos que o bucket seja **PRIVADO**. Após a autenticação, uma URL assinada temporária será gerada para permitir o download do arquivo.

**IMPORTANTE:** Não coloque `/` no início do prefixo (`prefix`) ou no nome do arquivo a ser compartilhado (parâmetro `file` no serviço de criação do QRCode).

Configuração:

Campo | Descrição
--- | ---
`region` | Região AWS
`bucket` | Identificador do bucket
`prefix` | Prefixo acrescido antes do ID do QRCode. O identificador do arquivo será `{prefix}{id}.json`
`ttl` | Tempo de validade do link de download, em segundos *(se não informado, será de 15 minutos)*
`aws_access_key_id` | Identificador da chave
`aws_secret_access_key` | Chave de acesso à API AWS

```json
{
    "region": "sa-east-1",
    "bucket": "<bucket>",
    "prefix": "<prefixo para arquivos de metadados do QRCode>",
    "ttl": 900,
    "aws_access_key_id": "",
    "aws_secret_access_key": ""
}
```

#### `StorageDiskAdapter` (filesystem local)

Salva os metadados no sistema de arquivos do **container**. Importante configurar o mapeamento para um local persistente.

Configuração:

Campo | Descrição
--- | ---
`path` | Pasta para salvar os arquivos JSON de cada QRCode
`prefix` | Prefixo acrescido antes do ID do QRCode. O nome do arquivo será `{prefix}{id}.json`

```json
{
    "path": "/var/www/data/files",
    "prefix": "qrcode-"
}
```

### Execução

Utilize o `docker-compose` e depois execute no container o `composer` para instalar as dependências.

```bash
docker-compose up
docker exec -it share-by-qrcode /bin/bash
composer install
exit
```

---------------------

## Serviços / Integração

Na pasta `docs` há uma coleção do Postman que pode ser usada como referência.

### Criar um QRCode

Serviço: `POST https://<site_do_servico>/d`

Authentication: `Basic` *(veja a Configuração)*

Content-Type: `application/json`

**Requisição:**

Parâmetro | Tipo | Descrição
--- | --- | ---
`file` | String | *(Obrigatório)* Nome ou identificador do arquivo a ser compartilhado. Se incluir a sequência `{ID}`, ela será substituída pelo ID único do QRCode.
`access_code` | String | Código de acesso para autenticação. Se não for informado, um código pseudo-aleatório será gerado.
`metadata` | JSON | Se informado, será salvo com as demais informações do QRCode, e será retornado ao consultar o QRCode.


```json
{
    "file": "/var/www/data/files/exemplo-{ID}.txt",
    "metadata": {
        "descricao": "Este é apenas um teste"
    }
}
```

**Resposta:**

Campo | Descrição
--- | ---
`id` | ID único do QRCode gerado
`file` | Identificador do arquivo a ser compartilhado *(a sequência `{ID}` já foi substituída)*.
`url` | URL para compartilhamento.
`gif` | Base64 da imagem GIF do QRCode com a `url`.
`access_code.type` | `internal` quando for gerado automaticamente, `external` quando for informado na requisição.
`access_code.value` | Código de Acesso que deve ser informado para acessar o arquivo.

```json
{
    "id": "qtnu8exbay4",
    "file": "/var/www/data/files/exemplo-qtnu8exbay4.txt",
    "access_code": {
        "type": "internal",
        "value": "88B76F"
    },
    "metadata": {
        "descricao": "Este é apenas um teste"
    },
    "url": "https://localhost:8086/d/qtnu8exbay4",
    "gif": "<imagem GIF em Base64>",
    "_links": {
        "self": {
            "href": "http://localhost:8085/d/qtnu8exbay4"
        }
    }
}
```

### Download do Arquivo

Serviço: `GET https://<site_do_servico>/d/<ID>`

**IMPORTANTE**: o `id` faz parte da URL e não é um Query Param.

**Requisição:**

Query Param | Tipo | Descrição
--- | --- | ---
`_format` | String | *(Obrigatório)* Deve ser `application/validador-iti+json`.
`_secretCode` | String | *(Obrigatório)* Código de Acesso associado.
`_frontend` | String | Se for `true`, a resposta será um JSON completo. Se não informado, ou tiver outro valor, a resposta será no padrão esperado pelo ITI.


```http
GET https://localhost:8086/d/qtnu8exbay4?_format=application/validador-iti+json&_secretCode=88B76F&_frontend=true
```

**Resposta com `_frontend=true`:**

Campo | Descrição
--- | ---
`id` | ID único do QRCode gerado
`file` | Identificador do arquivo a ser compartilhado.
`download` | URL para download do arquivo.
`access_code.type` | `internal` quando for gerado automaticamente, `external` quando for informado na requisição.
`access_code.value` | Código de Acesso que deve ser informado para acessar o arquivo.

```json
{
    "id": "qtnu8exbay4",
    "file": "/var/www/data/files/exemplo-qtnu8exbay4.txt",
    "access_code": {
        "type": "internal",
        "value": "88B76F"
    },
    "metadata": {
        "descricao": "Este é apenas um teste"
    },
    "download": "https://<site_para_download>/var/www/data/files/exemplo-qtnu8exbay4.txt"
}
```

**Resposta padrão ITI:**

```json
{
    "version": "1.0.0",
    "prescription": {
        "signatureFiles": [
            {
                "url": "https://<site_para_download>/var/www/data/files/exemplo-qtnu8exbay4.txt"
            }
        ]
    }
}
```
