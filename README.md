# Api Ration Bundle

The lib to cast a request to a Model object and cast a Model object to a response.

![lib tests](https://github.com/yapro/apiration-bundle/actions/workflows/main.yml/badge.svg)

## Installation on PHP 7

Add as a requirement in your `composer.json` file or run for prod:
```sh
composer require yapro/apiration-bundle laminas/laminas-code:3.4.1
```
As dev:
```sh
composer require yapro/apiration-bundle dev-master
```

## CORS (Optionally)

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

Tests
------------
```sh
docker build -t yapro/apiration-bundle:latest -f ./Dockerfile ./
docker run --user=1000:1000 --rm -v $(pwd):/app yapro/apiration-bundle:latest bash -c "cd /app \
  && composer install --optimize-autoloader --no-scripts --no-interaction \
  && vendor/bin/simple-phpunit --testsuite=Unit,Functional"
```

Dev
------------
```sh
docker build -t yapro/apiration-bundle:latest -f ./Dockerfile ./
docker run --user=1000:1000 --add-host=host.docker.internal:host-gateway -it --rm -v $(pwd):/app -w /app yapro/apiration-bundle:latest bash
composer install -o
```
Debug PHP:
```sh
PHP_IDE_CONFIG="serverName=common" \
XDEBUG_SESSION=common \
XDEBUG_MODE=debug \
XDEBUG_CONFIG="max_nesting_level=200 client_port=9003 client_host=host.docker.internal" \
vendor/bin/simple-phpunit --cache-result-file=/tmp/phpunit.cache -v --stderr --stop-on-incomplete --stop-on-defect \
--stop-on-failure --stop-on-warning --fail-on-warning --stop-on-risky --fail-on-risky --testsuite=Unit,Functional
```

Cs-Fixer:
```sh
docker run --user=1000:1000 --rm -v $(pwd):/app -w /app yapro/apiration-bundle:latest ./php-cs-fixer.phar fix --config=.php-cs-fixer.dist.php -v --using-cache=no --allow-risky=yes
```

Update phpmd rules:
```shell
docker run --user=1000:1000 --rm -v $(pwd):/app -w /app yapro/apiration-bundle:latest ./phpmd.phar . text phpmd.xml --exclude .github/workflows,vendor --strict --generate-baseline
```
