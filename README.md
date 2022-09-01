# Api Ration Bundle

The lib to cast a request to a Model object and cast a Model object to a response.

![lib tests](https://github.com/yapro/apiration-bundle/actions/workflows/main.yml/badge.svg)

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
docker run --user=1000:1000 --rm -v $(pwd):/app -w /app yapro/apiration-bundle:latest ./php-cs-fixer.phar fix --config=.php-cs-fixer.dist.php -v --using-cache=no --allow-risky=yes
```

Update phpmd rules:
```shell
docker run --user=1000:1000 --rm -v $(pwd):/app -w /app yapro/apiration-bundle:latest ./phpmd.phar . text phpmd.xml --exclude .github/workflows,vendor --strict --generate-baseline
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
