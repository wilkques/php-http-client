<?php

namespace Wilkques\Http\Tests;

use Wilkques\Http\Response;
use Wilkques\Http\Exceptions\RequestException;
use Wilkques\Http\Exceptions\CurlExecutionException;

class ResponseTest extends TestCase
{
    /**
     * Build the same raw string + info array shape curl_exec()/curl_getinfo()
     * hand to Response's constructor (CURLOPT_HEADER + CURLOPT_RETURNTRANSFER
     * both on: header block, then body, concatenated into one string).
     *
     * @param int $status
     * @param array $headers
     * @param string $body
     *
     * @return array [$raw, $info]
     */
    protected function buildRaw($status, array $headers, $body)
    {
        $lines = array("HTTP/1.1 {$status} Status");

        foreach ($headers as $key => $value) {
            $lines[] = "{$key}: {$value}";
        }

        $headerBlock = implode("\r\n", $lines) . "\r\n\r\n";

        return array(
            $headerBlock . $body,
            array('http_code' => $status, 'header_size' => strlen($headerBlock)),
        );
    }

    public function testParsesStatusHeadersAndBody()
    {
        list($raw, $info) = $this->buildRaw(200, array('Content-Type' => 'application/json'), '{"a":1}');

        $response = new Response($raw, $info);

        $this->assertEquals(200, $response->status());
        $this->assertEquals('application/json', $response->header('Content-Type'));
        $this->assertEquals('{"a":1}', $response->body());
    }

    public function testStatusCategoryHelpers()
    {
        list($raw, $info) = $this->buildRaw(200, array(), '');
        $response = new Response($raw, $info);
        $this->assertTrue($response->successful());
        $this->assertTrue($response->ok());
        $this->assertFalse($response->failed());

        list($raw, $info) = $this->buildRaw(301, array(), '');
        $response = new Response($raw, $info);
        $this->assertTrue($response->redirect());

        list($raw, $info) = $this->buildRaw(404, array(), '');
        $response = new Response($raw, $info);
        $this->assertTrue($response->clientError());
        $this->assertTrue($response->failed());
        $this->assertFalse($response->successful());

        list($raw, $info) = $this->buildRaw(500, array(), '');
        $response = new Response($raw, $info);
        $this->assertTrue($response->serverError());
        $this->assertTrue($response->failed());
    }

    public function testJsonDecodesBody()
    {
        list($raw, $info) = $this->buildRaw(200, array(), '{"foo":"bar","n":1}');

        $response = new Response($raw, $info);

        $this->assertEquals(array('foo' => 'bar', 'n' => 1), $response->json());
    }

    public function testToStringReturnsBody()
    {
        list($raw, $info) = $this->buildRaw(200, array(), 'plain body');

        $response = new Response($raw, $info);

        $this->assertEquals('plain body', (string) $response);
    }

    public function testArrayAccessGetAndExists()
    {
        list($raw, $info) = $this->buildRaw(200, array(), '{"foo":"bar"}');

        $response = new Response($raw, $info);

        $this->assertTrue(isset($response['foo']));
        $this->assertEquals('bar', $response['foo']);
        $this->assertFalse(isset($response['missing']));
    }

    public function testMagicGetReturnsDefinedPropertyOrJsonKey()
    {
        list($raw, $info) = $this->buildRaw(200, array(), '{"foo":"bar"}');

        $response = new Response($raw, $info);

        $this->assertEquals(200, $response->httpStatus);
        $this->assertEquals('bar', $response->foo);
    }

    public function testThrowDoesNotThrowWhenSuccessful()
    {
        list($raw, $info) = $this->buildRaw(200, array(), 'ok');

        $response = new Response($raw, $info);

        $this->assertSame($response, $response->throwException());
    }

    public function testThrowThrowsRequestExceptionWhenFailed()
    {
        list($raw, $info) = $this->buildRaw(500, array(), 'boom');

        $response = new Response($raw, $info);

        $this->expectExceptionCompat('Wilkques\\Http\\Exceptions\\RequestException');

        $response->throwException();
    }

    public function testThrowWithCustomExceptionInstance()
    {
        list($raw, $info) = $this->buildRaw(500, array(), 'boom');

        $response = new Response($raw, $info);

        $custom = new CurlExecutionException('custom message');

        $this->expectExceptionCompat('Wilkques\\Http\\Exceptions\\CurlExecutionException');

        try {
            $response->throwException($custom);
        } catch (CurlExecutionException $e) {
            $this->assertSame($custom, $e);

            throw $e;
        }
    }

    public function testThrowIfThrowsWhenConditionIsTrue()
    {
        list($raw, $info) = $this->buildRaw(200, array(), 'ok');

        $response = new Response($raw, $info);

        $this->expectExceptionCompat('Wilkques\\Http\\Exceptions\\RequestException');

        // successful() response, but throwIf() ignores failed()/successful()
        // entirely and throws purely off the given condition.
        $response->throwIf(true);
    }

    public function testThrowIfDoesNotThrowWhenConditionIsFalse()
    {
        list($raw, $info) = $this->buildRaw(500, array(), 'boom');

        $response = new Response($raw, $info);

        $this->assertSame($response, $response->throwIf(false));
    }

    public function testThrowIfAcceptsACallable()
    {
        list($raw, $info) = $this->buildRaw(200, array(), 'ok');

        $response = new Response($raw, $info);

        $this->expectExceptionCompat('Wilkques\\Http\\Exceptions\\RequestException');

        $response->throwIf(function ($response) {
            return $response->status() === 200;
        });
    }

    public function testThrowUnlessThrowsWhenConditionIsFalse()
    {
        list($raw, $info) = $this->buildRaw(200, array(), 'ok');

        $response = new Response($raw, $info);

        $this->expectExceptionCompat('Wilkques\\Http\\Exceptions\\RequestException');

        $response->throwUnless(false);
    }

    public function testThrowUnlessDoesNotThrowWhenConditionIsTrue()
    {
        list($raw, $info) = $this->buildRaw(500, array(), 'boom');

        $response = new Response($raw, $info);

        $this->assertSame($response, $response->throwUnless(true));
    }
}
