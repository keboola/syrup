<?php
use Composer\Autoload\ClassLoader;
use Doctrine\Common\Annotations\AnnotationRegistry;

/** @var $loader ClassLoader */
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    $loader = require __DIR__.'/../vendor/autoload.php';
} else {
    $loader = require __DIR__.'/../../../autoload.php';
}
AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
return $loader;
