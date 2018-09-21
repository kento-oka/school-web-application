<?php
namespace App;

class AppBundle extends \Fratily\Kernel\Bundle\Bundle{

    public function registerContainerConfigurations(): array{
        return [
            new Container\TwigConfig(),
        ];
    }

    public function registerControllers(): array{
        return [
            \App\Controller\IndexController::class,
        ];
    }
}