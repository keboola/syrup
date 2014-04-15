<?php

use Composer\Autoload\ClassLoader;
use Doctrine\Common\Annotations\AnnotationRegistry;

/**
 * @var $loader ClassLoader
 */

//@todo somehow detect whether this bundle is in vendor or not

// is in vendor
//$loader = require __DIR__.'/../../../autoload.php';

// is not
$loader = require __DIR__.'/../vendor/autoload.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

return $loader;
