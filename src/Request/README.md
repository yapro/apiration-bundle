Преобразование запросов
--
Вначале был создан ControllerActionParameterConverter, он был среди других request-param-конвертеров:
```shell script
bin/console debug:container --tag=request.param_converter
```
К сожалению, request.param_converter не дает возможности определить вид объекта в массиве объектов, поэтому был
создан ControllerActionArgumentResolver (tag=controller.argument_value_resolver) в котором можно из request-a вытащить 
Controller::action и с помощью рефлексии посмотреть php-doc аннотацию (он срабатывает после request.param_converter).
```shell script
bin/console debug:container --tag=controller.argument_value_resolver
```
Конец.

Заметка: в ControllerActionParameterConverter::supports приходит "ParamConverter $configuration" который создается в 
\Sensio\Bundle\FrameworkExtraBundle\EventListener\ParamConverterListener::onKernelController с помощью тега 
kernel.event_subscriber. Но в "ParamConverter $configuration" нет информации о Controller::action, однако информацию 
можно добавить, если обернуть ParamConverterListener в свой (и привет рефлексия).

Заметка: если реализовать через https://symfony.com/doc/current/reference/events.html#kernel-controller-arguments
то скорее всего можно будет отказаться от SensioFrameworkExtraBundle
