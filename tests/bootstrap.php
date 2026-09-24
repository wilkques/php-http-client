<?php

// This is the standalone package repository, so the package root is one
// directory up and its own Composer autoloader lives at
// ../vendor/autoload.php. Run `composer install` at the package root
// before running the suite.
require __DIR__ . '/../vendor/autoload.php';

// The autoloader above only knows about "Wilkques\Http\" -> "src/".
// Register a second, PSR-4-ish autoloader for this package's own test
// fixtures/classes so that "Wilkques\Http\Tests\Foo\Bar" resolves to
// "tests/Foo/Bar.php" relative to this file.
spl_autoload_register(function ($class) {
    $prefix = 'Wilkques\\Http\\Tests\\';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));

    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

// See wilkques/filesystem's tests/bootstrap.php, which this mirrors:
// composer.json's require-dev pins a PHPUnit range that differs across the
// PHP versions this suite runs under, and two things differ across that
// range that are compile-time syntax, not something a runtime check alone
// can paper over.
if (!class_exists('PHPUnit\\Framework\\TestCase') && class_exists('PHPUnit_Framework_TestCase')) {
    class_alias('PHPUnit_Framework_TestCase', 'PHPUnit\\Framework\\TestCase');
}

if (!defined('WILKQUES_HTTP_TESTS_SETUP_NEEDS_VOID')) {
    $needsVoid = false;

    if (method_exists('ReflectionMethod', 'hasReturnType')) {
        $setUpReflection = new ReflectionMethod('PHPUnit\\Framework\\TestCase', 'setUp');
        $needsVoid = $setUpReflection->hasReturnType();
    }

    define('WILKQUES_HTTP_TESTS_SETUP_NEEDS_VOID', $needsVoid);
}
