<?php
namespace App;

class AppBundle extends \Fratily\Kernel\Bundle\Bundle{

    public function registerContainers(): array{
        return [
            Container\TwigContainer::class,
        ];
    }

    public function registerControllers(): array{
        return [
            Controller\IndexController::class,
        ];
    }
}