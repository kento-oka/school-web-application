<?php
require "../vendor/autoload.php";

$dotenv = new Dotenv\Dotenv(__DIR__ . "/..");
$dotenv->load();

$kernel = new \Fratily\Kernel\Kernel(
    "prod",
    false,
    [
        App\AppBundle::class,
        Fratily\Bundle\Framework\FrameworkBundle::class,
    ]
);

$request    = (new \Fratily\Http\Message\ServerRequestFactory())->createServerRequest(
    $_SERVER["REQUEST_METHOD"],
    "http://localhost/web02006/login/teacher/teacher.php",
    $_SERVER
);

$emitter    = new Fratily\Http\Message\Response\Emitter();
$emitter->emit($kernel->handle($request));