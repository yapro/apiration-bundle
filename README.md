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
More [examples](tests/FunctionalExt/App/Controller/AppController.php) and [tests](tests/Functional/Api/JsonTest.php)

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
