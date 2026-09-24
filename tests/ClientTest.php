<?php

namespace Wilkques\Http\Tests;

use Wilkques\Http\Client;
use Wilkques\Http\Exceptions\CurlExecutionException;

class ClientTest extends TestCase
{
    public function testGetSendsMethodAndQueryString()
    {
        $response = (new Client)->get($this->serverUrl(), array('a' => '1', 'b' => '2'));

        $this->assertTrue($response->ok());

        $json = $response->json();

        $this->assertEquals('GET', $json['method']);
        $this->assertEquals(array('a' => '1', 'b' => '2'), $json['query']);
    }

    public function testPostAsFormSendsUrlEncodedBody()
    {
        $response = (new Client)->asForm()->post($this->serverUrl(), array('x' => '1', 'y' => '2'));

        $this->assertTrue($response->ok());

        $json = $response->json();

        $this->assertEquals('POST', $json['method']);
        $this->assertEquals(array('x' => '1', 'y' => '2'), $json['post']);
    }

    public function testPutAsFormSendsBody()
    {
        $response = (new Client)->asForm()->put($this->serverUrl(), array('x' => 'put-value'));

        $json = $response->json();

        // PHP's $_POST is only ever populated for an actual POST request
        // (a built-in PHP/SAPI behavior, not something this package
        // controls), so PUT/PATCH bodies must be read back from the raw
        // body instead of $_POST here.
        parse_str($json['body'], $parsed);

        $this->assertEquals('PUT', $json['method']);
        $this->assertEquals(array('x' => 'put-value'), $parsed);
    }

    public function testPatchAsFormSendsBody()
    {
        $response = (new Client)->asForm()->patch($this->serverUrl(), array('x' => 'patch-value'));

        $json = $response->json();

        parse_str($json['body'], $parsed);

        $this->assertEquals('PATCH', $json['method']);
        $this->assertEquals(array('x' => 'patch-value'), $parsed);
    }

    public function testDeleteSendsMethodAndQueryString()
    {
        $response = (new Client)->delete($this->serverUrl(), array('id' => '42'));

        $json = $response->json();

        $this->assertEquals('DELETE', $json['method']);
        $this->assertEquals(array('id' => '42'), $json['query']);
    }

    public function testWithHeadersAndWithToken()
    {
        $response = (new Client)
            ->withHeaders(array('X-Custom' => 'yes'))
            ->withToken('secret-token')
            ->get($this->serverUrl());

        $headers = $response->json()['headers'];

        $this->assertEquals('yes', $headers['X-CUSTOM']);
        $this->assertEquals('Bearer secret-token', $headers['AUTHORIZATION']);
    }

    public function testContentTypeHelpers()
    {
        $client = new Client;

        // Constructor already calls asJson(), confirm the helpers actually
        // change the header rather than being no-ops.
        $this->assertEquals('application/json; charset=utf-8', $client->getHeader('Content-Type'));

        $client->asForm();
        $this->assertEquals('application/x-www-form-urlencoded; charset=utf-8', $client->getHeader('Content-Type'));

        $client->asMultipart();
        $this->assertEquals('multipart/form-data', $client->getHeader('Content-Type'));
    }

    public function testAttachUploadsFile()
    {
        $path = tempnam(sys_get_temp_dir(), 'wilkques-http-test-');
        file_put_contents($path, 'file-contents-here');

        $response = (new Client)
            ->attach('upload', $path, 'text/plain', 'renamed.txt')
            ->post($this->serverUrl(), array('field' => 'value'));

        @unlink($path);

        $json = $response->json();

        $this->assertEquals('POST', $json['method']);
        $this->assertArrayHasKey('upload', $json['files']);
        $this->assertEquals('renamed.txt', $json['files']['upload']['name']);
        $this->assertEquals('file-contents-here', $json['files']['upload']['content']);
        $this->assertEquals('value', $json['post']['field']);
    }

    public function testStatusCodeHelpers()
    {
        $notFound = (new Client)->get($this->serverUrl('?status=404'));
        $this->assertTrue($notFound->clientError());
        $this->assertTrue($notFound->failed());
        $this->assertFalse($notFound->successful());

        $serverError = (new Client)->get($this->serverUrl('?status=500'));
        $this->assertTrue($serverError->serverError());
        $this->assertTrue($serverError->failed());

        $redirect = (new Client)->get($this->serverUrl('?status=301'));
        $this->assertTrue($redirect->redirect());
    }

    public function testUnreachableHostThrowsCurlExecutionException()
    {
        $this->expectExceptionCompat('Wilkques\\Http\\Exceptions\\CurlExecutionException');

        (new Client)->get('http://this-host-does-not-resolve.invalid/');
    }

    public function testStaticCallProxiesUndefinedMethodsToNewInstance()
    {
        // get()/post()/etc. are real instance methods, so they don't
        // exercise __callStatic (calling a non-static method statically
        // fatals on PHP 8+). init() only exists on CurlHandle, reached
        // through Client's own __call() -> newCurl()->init(), so this is
        // the part of the proxy chain __callStatic actually needs to cover.
        $handle = Client::init();

        $this->assertInstanceOf('Wilkques\\Http\\CurlHandle', $handle);
    }
}
