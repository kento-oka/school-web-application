<?php
namespace App\Container;

use Fratily\Container\Builder\AbstractContainer;
use Fratily\Container\Builder\ContainerBuilderInterface;

class TwigContainer extends AbstractContainer{

    /**
     * {@inheritdoc}
     */
    public static function build(ContainerBuilderInterface $builder, array $options){
        $builder->add(
            "app.twig.loader",
            new \Twig_Loader_Filesystem([__DIR__ . "/../../template"]),
            ["twig.loader"]
        );
    }

    public static function modify(\Fratily\Container\Container $container){
    }
}