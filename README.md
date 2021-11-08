Installation
------------

Add as a requirement in your `composer.json` file or run
```sh
composer require yapro/monologext dev-master
```

If you wish to cast request to Model object:
```yaml
    YaPro\ApiRation\Request\ControllerActionArgumentResolver:
      tags:
        - { name: controller.argument_value_resolver, priority: 150 }

    # Объекты ApiRationObjectInterface автоматически преобразовываются в json с помощью ToJsonConverter-а.
    YaPro\ApiRation\Response\ToJsonConverter:
        tags:
            - { name: kernel.event_listener, event: kernel.view, priority: 0, method: onKernelView }

    YaPro\ApiRation\Exception\ExceptionResolver:
        tags:
            - { name: kernel.event_listener, event: kernel.exception }
```

```yaml
    Symfony\Component\Serializer\Encoder\JsonDecode: ~
    Symfony\Component\Serializer\Encoder\JsonEncode: ~
```

## Optionally

### CORS

```yaml
    YaPro\ApiRation\Response\CorsResolver:
        tags:
            - { name: kernel.event_subscriber }
```

Tests
------------
```sh
docker build -t yapro/apiration:latest -f ./Dockerfile ./
docker run --rm -v $(pwd):/app yapro/apiration:latest bash -c "cd /app \
  && composer install --optimize-autoloader --no-scripts --no-interaction \
  && vendor/bin/phpunit --testsuite=Unit"
```

Dev
------------
```sh
docker build -t yapro/apiration:latest -f ./Dockerfile ./
docker run -it --rm -v $(pwd):/app -w /app yapro/apiration:latest bash
composer install -o
```

,
"symfony/framework-bundle": "*",
"symfony/browser-kit": "*",
"yapro/symfony-http-test-ext": "^1.0",
"yapro/doctrine-ext": "dev-master"
