<?php

namespace Wilkques\Http;

use Wilkques\Helpers\Arrays;
use Wilkques\Http\Exceptions\RequestException;

/**
 * A class represents API response.
 */
class Response implements \JsonSerializable, \ArrayAccess
{
    /** @var int */
    private $httpStatus;

    /** @var string */
    private $body;

    /** @var string[] */
    private $headers;

    /**
     * Response constructor.
     *
     * @param string|null $result
     * @param array|null $info
     */
    public function __construct($result = null, array $info = array())
    {
        $this->response($result, $info);
    }

    /**
     * @param string|null $result
     *
     * @return static
     */
    protected function response($result = null, array $info = array())
    {
        $httpStatus = $info['http_code'];
        $responseHeaderSize = $info['header_size'];

        return $this->setHttpStatus($httpStatus)
            ->setHeaders($this->responseHeaders($result, $responseHeaderSize))
            ->setBody($this->bodyHandle($result, $responseHeaderSize));
    }

    /**
     * @param string $result
     * @param integer $responseHeaderSize
     *
     * @return array
     */
    protected function responseHeaders($result, $responseHeaderSize)
    {
        $responseHeaderStr = substr($result, 0, $responseHeaderSize);

        $responseHeaders = array();

        foreach (explode("\r\n", $responseHeaderStr) as $responseHeader) {
            $kv = explode(':', $responseHeader, 2);

            if (count($kv) === 2) {
                $responseHeaders[$kv[0]] = trim($kv[1]);
            }
        }

        return $responseHeaders;
    }

    /**
     * @param string $result
     * @param integer $responseHeaderSize
     *
     * @return string|false
     */
    protected function bodyHandle($result, $responseHeaderSize)
    {
        return substr($result, $responseHeaderSize);
    }

    /**
     * @return RequestException
     */
    protected function getThrows()
    {
        return new RequestException($this);
    }

    /**
     * @param callable|\Exception|null $callable
     *
     * @throws RequestException|\UnexpectedValueException|\Exception
     *
     * @return static
     */
    public function throwException($throw = null)
    {
        if ($this->failed()) {
            if (is_callable($throw) || $throw instanceof \Closure) {
                throw $this->returnExceptionCheck($throw($this, $this->getThrows()));
            }

            if ($throw instanceof \Exception) {
                throw $throw;
            }

            if ($throw) {
                throw $this->returnExceptionCheck($throw);
            }

            throw $this->getThrows();
        }

        return $this;
    }

    /**
     * @param mixed $callable
     *
     * @throws \UnexpectedValueException
     *
     * @return mixed
     */
    protected function returnExceptionCheck($callable = null)
    {
        if (is_null($callable))
            return $this->getThrows();
        else if (!$callable instanceof \Exception)
            return new \UnexpectedValueException("throw return must be Exception");

        return $callable;
    }

    /**
     * @param int $code
     *
     * @return static
     */
    public function setHttpStatus($code = 200)
    {
        $this->httpStatus = $code;

        return $this;
    }

    /**
     * Returns HTTP status code of response.
     *
     * @return int HTTP status code of response.
     */
    public function getHttpStatus()
    {
        return $this->httpStatus;
    }

    /**
     * Get the status code of the response.
     *
     * @return int
     */
    public function status()
    {
        return (int) $this->getHttpStatus();
    }

    /**
     * @param string $body
     *
     * @return static
     */
    public function setBody($body = '')
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Returns raw response body.
     *
     * @return string Raw request body.
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return string
     */
    public function body()
    {
        return (string) $this->getBody();
    }

    /**
     * Returns response body as array (it means, returns JSON decoded body).
     *
     * @return array Request body that is JSON decoded.
     */
    public function json()
    {
        return json_decode($this->body(), true);
    }

    /**
     * Returns the value of the specified response header.
     *
     * @param string $name A String specifying the header name.
     *
     * @return string|null A response header string, or null if the response does not have a header of that name.
     */
    public function header($key)
    {
        return isset($this->headers[$key]) ? $this->headers[$key] : null;
    }

    /**
     * @param array $headers
     *
     * @return static
     */
    public function setHeaders(array $headers = array())
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Returns all of response headers.
     *
     * @return string[] All of the response headers.
     */
    public function headers()
    {
        return $this->headers;
    }

    /**
     * Determine if the request was successful.
     *
     * @return bool
     */
    public function successful()
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    /**
     * Determine if the response code was "OK".
     *
     * @return bool
     */
    public function ok()
    {
        return $this->status() === 200;
    }

    /**
     * Determine if the response was a redirect.
     *
     * @return bool
     */
    public function redirect()
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    /**
     * Determine if the response indicates a client or server error occurred.
     *
     * @return bool
     */
    public function failed()
    {
        return $this->serverError() || $this->clientError();
    }

    /**
     * Determine if the response indicates a client error occurred.
     *
     * @return bool
     */
    public function clientError()
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    /**
     * Determine if the response indicates a server error occurred.
     *
     * @return bool
     */
    public function serverError()
    {
        return $this->status() >= 500;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->body();
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->json();
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        $json = $this->json();

        return $json[$offset];
    }

    /**
     * Set the value for a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $json = $this->json();

        $json[$offset] = $value;
    }

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed  $offset
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return Arrays::has($this->json(), $offset);
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        $json = $this->json();

        unset($json[$offset]);
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string  $key
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        if (property_exists($this, $key)) {
            return $this->{$key};
        }

        $json = $this->json();

        return $json[$key];
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        if (property_exists($this, $key)) {
            $this->{$key} = $value;
        } else {
            $json = $this->json();

            $json[$key] = $value;
        }
    }
}
