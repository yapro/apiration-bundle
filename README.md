# Api Ration Bundle

The lib to cast a request to a Model object and cast a Model object to a response.

## Installation

Add as a requirement in your `composer.json` file or run
```sh
composer require yapro/apiration-bundle dev-master
```

## CORS (Optionally)

```yaml
    YaPro\ApiRationBundle\Response\CorsResolver:
        tags:
            - { name: kernel.event_subscriber }
```

If the library doesn't work, please add the following lines to services.yml:
```yaml
    Symfony\Component\Serializer\Encoder\JsonDecode: ~
    Symfony\Component\Serializer\Encoder\JsonEncode: ~
```

Tests
------------
```sh
docker build -t yapro/apiration-bundle:latest -f ./Dockerfile ./
docker run --user=1000:1000 --rm -v $(pwd):/app yapro/apiration-bundle:latest bash -c "cd /app \
  && composer install --optimize-autoloader --no-scripts --no-interaction \
  && vendor/bin/phpunit --testsuite=Unit"
```

Dev
------------
```sh
docker build -t yapro/apiration-bundle:latest -f ./Dockerfile ./
docker run --user=1000:1000 -it --rm -v $(pwd):/app -w /app yapro/apiration-bundle:latest bash
composer install -o
```
Debug PHP:
```sh
docker run --user=1000:1000 --add-host=host.docker.internal:host-gateway --rm -v $(pwd):/app yapro/apiration-bundle:latest bash -c "cd /app \
  && composer install --optimize-autoloader --no-interaction \
  && PHP_IDE_CONFIG=\"serverName=common\" \
     XDEBUG_SESSION=common \
     XDEBUG_MODE=debug \
     XDEBUG_CONFIG=\"max_nesting_level=200 client_port=9003 client_host=host.docker.internal\" \
     vendor/bin/simple-phpunit --cache-result-file=/tmp/phpunit.cache --testsuite=Unit"
```

"yapro/symfony-http-test-ext": "^1.0",
"yapro/doctrine-ext": "dev-master"
