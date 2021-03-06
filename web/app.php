<?php

use Keboola\Syrup\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;

umask(0002);

define('ROOT_PATH', __DIR__.'/../');

$loader = require_once __DIR__.'/../app/bootstrap.php.cache';

// Use APC for autoloading to improve performance.
// Change 'sf2' to a unique prefix in order to prevent cache key conflicts
// with other applications also using APC.
/*
$loader = new ApcClassLoader('sf2', $loader);
$loader->register(true);
*/

Debug::enable('prod');

require_once __DIR__.'/../app/AppKernel.php';
//require_once __DIR__.'/../app/AppCache.php';

$kernel = new AppKernel('prod', true);
$kernel->loadClassCache();
$request = Request::createFromGlobals();

// Because of ELB we need to trust to all incoming requests
Request::setTrustedProxies(array($request->server->get('REMOTE_ADDR')));

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
