<?php

namespace Wilkques\Http\Tests;

use Wilkques\Http\Http;
use Wilkques\Http\Client;
use Wilkques\Http\Pool;

class HttpTest extends TestCase
{
    public function testInstanceCallDelegatesToClientMethod()
    {
        $http = new Http;

        $response = $http->get($this->serverUrl(), array('via' => 'http-facade'));

        $this->assertEquals(array('via' => 'http-facade'), $response->json()['query']);
    }

    public function testStaticCallDelegatesToClientMethod()
    {
        $response = Http::get($this->serverUrl(), array('via' => 'static-facade'));

        $this->assertEquals(array('via' => 'static-facade'), $response->json()['query']);
    }

    public function testNewClientReusesTheSameInstanceAcrossCalls()
    {
        $http = new Http;

        $client = $http->newClient();

        $this->assertInstanceOf('Wilkques\\Http\\Client', $client);
        $this->assertSame($client, $http->newClient());
    }

    public function testNewPoolReusesTheSameInstanceAcrossCalls()
    {
        $http = new Http;

        $pool = $http->newPool();

        $this->assertInstanceOf('Wilkques\\Http\\Pool', $pool);
        $this->assertSame($pool, $http->newPool());
    }

    public function testConstructorAcceptsExplicitClientAndPool()
    {
        $client = new Client;
        $pool = new Pool;

        $http = new Http($client, $pool);

        $this->assertSame($client, $http->getClient());
        $this->assertSame($pool, $http->getPool());
    }

    public function testCallFallsThroughToPoolWhenNotAClientMethod()
    {
        $http = new Http;

        // as() only exists on Pool, not Client, so __call() must fall
        // through newPool()->as() rather than newClient()->as().
        $client = $http->as('via-http-pool-fallthrough');

        $this->assertInstanceOf('Wilkques\\Http\\Client', $client);
    }
}
