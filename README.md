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

## Configuração

---------------------

## Serviços

Na pasta `docs` há uma coleção do Postman que pode ser usada como referência.

### Criar um QRCode

Serviço: `POST <url>/d`

Authentication: `Basic` *(veja a Configuração)*

Content-Type: `application/json`

Parâmetro | Tipo | Descrição
--- | --- | ---
`file` | String | *(Obrigatório)* Nome ou identificador do arquivo a ser compartilhado. Se incluir a sequência `{ID}`, ela será substituída pelo ID único do QRCode.
`access_code` | String | Código de acesso para autenticação. Se não for informado, um código pseudo-aleatório será gerado.
`metadata` | JSON | Se informado, será salvo com as demais informações do QRCode, e será retornado ao consultar o QRCode.

Requisição:
```json
{
    "file": "/var/www/data/files/exemplo-{ID}.txt",
    "metadata": {
        "descricao": "Este é apenas um teste"
    }
}
```

Resposta:
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
