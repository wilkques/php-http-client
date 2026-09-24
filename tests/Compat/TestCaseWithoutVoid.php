<?php

namespace Wilkques\Http\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    /**
     * Base URL of the local echo server started for this test run (see
     * /var/www/packages/_httptest/server/echo.php). Overridable via the
     * HTTP_TEST_SERVER env var so the same suite can run against a
     * differently-addressed server container per PHP version.
     *
     * @param string $path
     *
     * @return string
     */
    protected function serverUrl($path = '')
    {
        $base = getenv('HTTP_TEST_SERVER');

        if (!$base) {
            $base = 'http://http-echo-server:8080';
        }

        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }

    /**
     * PHPUnit assertion/expectation method names that have been renamed
     * across the major versions this suite runs under (see
     * tests/bootstrap.php re: "phpunit/phpunit" version range).
     *
     * @param string $class
     *
     * @return void
     */
    protected function expectExceptionCompat($class)
    {
        if (method_exists($this, 'expectException')) {
            $this->expectException($class);

            return;
        }

        $this->setExpectedException($class);
    }
}
