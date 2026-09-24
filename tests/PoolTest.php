<?php

namespace Wilkques\Http\Tests;

use Wilkques\Http\Pool;
use Wilkques\Http\Response;
use Wilkques\Http\Exceptions\CurlExecutionException;

class PoolTest extends TestCase
{
    public function testPoolRunsMultipleRequestsConcurrentlyAndKeysResponses()
    {
        $pool = new Pool;

        $serverUrl = $this->serverUrl();

        $responses = $pool->pool(function ($pool) use ($serverUrl) {
            $pool->alias('one')->get($serverUrl, array('n' => '1'));
            $pool->alias('two')->get($serverUrl, array('n' => '2'));
        });

        $this->assertInstanceOf('Wilkques\\Http\\Response', $responses['one']);
        $this->assertInstanceOf('Wilkques\\Http\\Response', $responses['two']);

        $oneJson = $responses['one']->json();
        $twoJson = $responses['two']->json();

        $this->assertEquals(array('n' => '1'), $oneJson['query']);
        $this->assertEquals(array('n' => '2'), $twoJson['query']);
    }

    public function testPoolRejectedCallbackReceivesCurlExecutionException()
    {
        $pool = new Pool;

        $serverUrl = $this->serverUrl();

        $rejectedException = null;

        $responses = $pool->pool(function ($pool) use ($serverUrl) {
            $pool->alias('good')->get($serverUrl);
            $pool->alias('bad')->get('http://this-host-does-not-resolve.invalid/');
        }, array(
            'rejected' => function (CurlExecutionException $e, $key) use (&$rejectedException) {
                $rejectedException = $e;

                return $e;
            },
        ));

        $this->assertInstanceOf('Wilkques\\Http\\Response', $responses['good']);
        $this->assertInstanceOf('Wilkques\\Http\\Exceptions\\CurlExecutionException', $responses['bad']);
        $this->assertNotNull($rejectedException);
    }

    public function testPoolSupportsAnonymousUnkeyedEntries()
    {
        // Pool::__call() (the anonymous-entry proxy, documented in
        // README.md's pool() example alongside alias()) was found
        // commented out — confirmed pre-existing, unrelated to the PHP
        // 5.3 downgrade — which made this exact documented usage fatal.
        $pool = new Pool;

        $serverUrl = $this->serverUrl();

        $responses = $pool->pool(function ($pool) use ($serverUrl) {
            $pool->get($serverUrl, array('n' => 'first'));
            $pool->get($serverUrl, array('n' => 'second'));
        });

        $this->assertCount(2, $responses);

        $values = array();

        foreach ($responses as $response) {
            $this->assertInstanceOf('Wilkques\\Http\\Response', $response);

            $json = $response->json();

            $values[] = $json['query']['n'];
        }

        sort($values);

        $this->assertEquals(array('first', 'second'), $values);
    }
}
