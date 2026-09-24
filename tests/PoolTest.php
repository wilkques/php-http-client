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
            $pool->as('one')->get($serverUrl, array('n' => '1'));
            $pool->as('two')->get($serverUrl, array('n' => '2'));
        });

        $this->assertInstanceOf('Wilkques\\Http\\Response', $responses['one']);
        $this->assertInstanceOf('Wilkques\\Http\\Response', $responses['two']);

        $this->assertEquals(array('n' => '1'), $responses['one']->json()['query']);
        $this->assertEquals(array('n' => '2'), $responses['two']->json()['query']);
    }

    public function testPoolRejectedCallbackReceivesCurlExecutionException()
    {
        $pool = new Pool;

        $serverUrl = $this->serverUrl();

        $rejectedException = null;

        $responses = $pool->pool(function ($pool) use ($serverUrl) {
            $pool->as('good')->get($serverUrl);
            $pool->as('bad')->get('http://this-host-does-not-resolve.invalid/');
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
}
