<?php
namespace App\Container;

use Fratily\Container\Container;

class TwigConfig extends \Fratily\Container\ContainerConfig{

    public function modify(Container $container){
        $loader = $container->get("twig.loader");

        $loader->addloader(new \Twig_Loader_Filesystem([__DIR__ . "/../../template"]));
    }
}