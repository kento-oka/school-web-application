<?php
require "../vendor/autoload.php";

$kernel = new \Fratily\Kernel\Kernel(
    "prod",
    false,
    [
        App\AppBundle::class,
        Fratily\Bundle\Framework\FrameworkBundle::class,
        Fratily\Bundle\Twig\TwigBundle::class,
    ]
);

$request    = (new \Fratily\Http\Message\ServerRequestFactory())->createServerRequest(
    $_SERVER["REQUEST_METHOD"],
    "http://localhost/web02006/login/student/student.php",
    $_SERVER
);

$emitter    = new Fratily\Http\Message\Response\Emitter();
$emitter->emit($kernel->handle($request));