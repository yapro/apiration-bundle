<?php

namespace YaPro\ApiRationBundle\Tests\FunctionalExt\App;

use YaPro\ApiRationBundle\ApiRationBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

use function mkdir;
use function mt_rand;

class AppKernel extends Kernel
{
    use MicroKernelTrait;

	public function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension(
            'framework',
            [
                'secret' => 'S0ME_SECRET',
                'test' => true,
            ]
        );
	    $container->services()
            ->load('YaPro\\ApiRationBundle\\Tests\\FunctionalExt\\App\\Controller\\', __DIR__ . '/Controller/*')
            ->autowire()
            ->autoconfigure();
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(__DIR__ . '/Controller/', 'annotation');
    }

    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new ApiRationBundle()
        ];
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return '/tmp/app-cache';
    }
}
