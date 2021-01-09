<?php

declare(strict_types=1);

use App\Kernel;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

if ((bool)$_SERVER['APP_DEBUG']) {
    ini_set('display_errors', 'On');
    error_reporting(-1);
}

$kernel = new Kernel($_SERVER['APP_NAME'], $_SERVER['APP_VERSION'], $_SERVER['APP_ENV'], (bool)$_SERVER['APP_DEBUG']);

$container = $kernel->getContainer();

/** @var ServerRequestCreatorInterface $requestCreator */
$requestCreator = $container->get(ServerRequestCreatorInterface::class);

/** @var RequestHandlerInterface $handler */
$handler = $container->get(RequestHandlerInterface::class);

/** @var EmitterInterface $emitter */
$emitter = $container->get(EmitterInterface::class);

$request = $requestCreator->fromGlobals();
$response = $handler->handle($request);
$emitter->emit($response);