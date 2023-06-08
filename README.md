# Api Ration Bundle

The lib to casts a request to a Model object and casts a Model object to a response.

![lib tests](https://github.com/yapro/apiration-bundle/actions/workflows/main.yml/badge.svg)

## How to use

1. Make an ApiRationObject, example SimpleModel
```php
<?php

declare(strict_types=1);

namespace App;

use YaPro\ApiRationBundle\Marker\ApiRationObjectInterface;

class SimpleModel implements ApiRationObjectInterface
{
    private string $varString;
    private bool $varBoolean;

    public function __construct(string $varString, bool $varBoolean) {
        $this->varString = $varString;
        $this->varBoolean = $varBoolean;
    }

    public function getVarString(): string
    {
        return $this->varString;
    }

    public function isVarBoolean(): bool
    {
        return $this->varBoolean;
    }
}
```
2. Use the SimpleModel in controller action (specify the namespace completely)
```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AppController extends AbstractController
{
    /**
     * @Route("/api-json-test/simple-model")
     *
     * @param \App\SimpleModel $model
     *
     * @return SimpleModel
     */
    public function getSimpleModel(SimpleModel $model, Request $request): SimpleModel
    {
        return $model;
    }
}
```
3. Make the curl request
```shell
curl -X GET "localhost/api-json-test/simple-model" -H 'Content-Type: application/json' -d'
{
  "varString": "string",
  "varBoolean": "true"
}
'
```
4. Get the answer
```shell
{"varString":"string","varBoolean":true}
```
As you can see, any object which implements the ApiRationObjectInterface is automatically converted to json.

More [examples](tests/FunctionalExt/App/Controller/AppController.php) and [tests](tests/Functional/Api/JsonTest.php)

### JsonRequest - the simple way to work with Request

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use YaPro\ApiRationBundle\Request\JsonRequest;

class AppController extends AbstractController
{
    /**
     * @Route("/search", methods={"POST"})
     *
     * @param JsonRequest           $request
     * @param ArticleRepository $articleRepository
     *
     * @return CollectionJsonLdResponse
     */
    public function search(JsonRequest $request): JsonResponse
    {
        $userAddresses = $request->getArray(); // request: ["foo@go.com", "bar@go.com"]
        // OR:
        $myFieldValue = $request->getObject()->myField; // request: {"myField": "my value"}

        return $this->json([]);
    }
```

If you need to create a JsonLd response for the creation operation, try:

```php
        return new ResourceCreatedJsonLdResponse(
            $article->getId(),
            [
                'title' => $article->getTitle(),
                'text' => $article->getText(),
            ]
        );
```

If you need to create a JsonLd response for an update operation, try [ResourceUpdatedJsonLdResponse](src/Response/JsonLd/ResourceUpdatedJsonLdResponse.php). 

### How to make JsonLd Response (hydra:Collection)

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use YaPro\ApiRationBundle\Response\JsonLd\CollectionJsonLdResponse;

class AppController extends AbstractController
{
    /**
     * @Route("/search", methods={"GET"})
     *
     * @param Request           $request
     * @param ArticleRepository $articleRepository
     *
     * @return CollectionJsonLdResponse
     */
    public function search(Request $request, ArticleRepository $articleRepository): CollectionJsonLdResponse
    {
        $response = new CollectionJsonLdResponse($request);
        $searchValue = $request->query->get('searchValue', '');
        if (empty($searchValue)) {
            return $response;
        }

        $items = $this->getEntityManager()->getConnection()->fetchAll("
            SELECT 
                id,
                title
            FROM Article
            WHERE title LIKE :searchValue
            ORDER BY createdAt DESC
            LIMIT " . $response->getOffset() . ", " . $response->getLimit() . "
        ", [
             'searchValue' => $searchValue,
         ]);

        return $response->initData(
            $items,
            $this->getTotalItems()
        );
    }
}
```

Notice: symfony 6.3 is supports 
[similar features](https://symfony.com/blog/new-in-symfony-6-3-mapping-request-data-to-typed-objects), the bundle 
supports more functionality, for example, responding to an invalid request by throwing a BadRequestException:
```php
$message = 'Validation errors';
$errors = [
    'field_name' => 'The name cannot contain a number',
    'field_lastname' => [
        'The name cannot contain a number',
        'Name must be at least 2 characters long',
    ],
];
throw new BadRequestException($message, $errors);
```
and the client will receive the response with the status 400:
```shell
{
    "message": "Validation errors",
    "errors": [
        {
            "fieldName": "field_name",
            "messages": [
                "The name cannot contain a number"
            ]
        },
        {
            "fieldName": "field_lastname",
            "messages": [
                "The name cannot contain a number",
                "Name must be at least 2 characters long"
            ]
        }
    ]
}
```
More [examples](tests/Unit/Exception/BadRequestExceptionTest.php).

## Installation on PHP 7

Add as a requirement in your `composer.json` file or run for prod:
```sh
composer require yapro/apiration-bundle laminas/laminas-code:3.4.1
```

## Installation on PHP 8

Add as a requirement in your `composer.json` file or run for prod:
```sh
composer require yapro/apiration-bundle
```

As dev:
```sh
composer require yapro/apiration-bundle dev-master
```

Dev
------------
```sh
docker build -t yapro/apiration-bundle:latest -f ./Dockerfile ./
docker run --rm --user=$(id -u):$(id -g) --add-host=host.docker.internal:host-gateway -it --rm -v $(pwd):/app -w /app yapro/apiration-bundle:latest bash
cp -f composer.lock.php7 composer.lock
composer install -o
```
Debug tests:
```sh
PHP_IDE_CONFIG="serverName=common" \
XDEBUG_SESSION=common \
XDEBUG_MODE=debug \
XDEBUG_CONFIG="max_nesting_level=200 client_port=9003 client_host=host.docker.internal" \
vendor/bin/simple-phpunit --cache-result-file=/tmp/phpunit.cache -v --stderr --stop-on-incomplete --stop-on-defect \
--stop-on-failure --stop-on-warning --fail-on-warning --stop-on-risky --fail-on-risky --testsuite=Unit,Functional
```
If you need php8:
```sh
docker build -t yapro/apiration-bundle:latest --build-arg "PHP_VERSION=8" -f ./Dockerfile ./
cp -f composer.lock.php8 composer.lock
````

Cs-Fixer:
```sh
wget https://github.com/FriendsOfPHP/PHP-CS-Fixer/releases/download/v3.8.0/php-cs-fixer.phar && chmod +x ./php-cs-fixer.phar
./php-cs-fixer.phar fix --config=.php-cs-fixer.dist.php -v --using-cache=no --allow-risky=yes
```

Update phpmd rules:
```shell
wget https://github.com/phpmd/phpmd/releases/download/2.12.0/phpmd.phar && chmod +x ./phpmd.phar
./phpmd.phar . text phpmd.xml --exclude .github/workflows,vendor --strict --generate-baseline
```

## CORS (Optional functionality)

```yaml
    YaPro\ApiRationBundle\Cors\CorsResolver:
        tags:
            - { name: kernel.event_subscriber }
```

If the library doesn't work, try to add the following lines to services.yml:
```yaml
    Symfony\Component\Serializer\Encoder\JsonDecode: ~
    Symfony\Component\Serializer\Encoder\JsonEncode: ~
```
