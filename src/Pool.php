<?php

namespace Wilkques\Http;

use Wilkques\Helpers\Arrays;
use Wilkques\Http\Exceptions\CurlExecutionException;
use Wilkques\Http\Exceptions\CurlMultiExecutionException;

class Pool
{
    /** @var \Wilkques\Http\CurlMultiHandle */
    protected $handle;

    /** @var Client */
    protected $client;

    /** @var array */
    protected $pool;

    /** @var array */
    protected $options = [];

    public function __construct()
    {
        $this->boot();
    }

    public function boot()
    {
        return $this->setOptions([
            'response'  => [
                'sort'  => true
            ],
            'timeout'   => 100,
            'fulfilled' => function (Response $response, $key) {
                return $response;
            },
            'rejected'  => function (CurlExecutionException $e, $key) {
                return $e;
            },
            'runtimeRejected' => function (CurlMultiExecutionException $e) {
                return $e;
            }
        ]);
    }

    /**
     * @return \Wilkques\Http\CurlMultiHandle
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * @return CurlMultiHandle
     */
    public function newMultiHandle()
    {
        return $this->handle = $this->getHandle() ?? new CurlMultiHandle;
    }

    /**
     * @param array $options
     * 
     * @return static
     */
    public function setOptions(array $options)
    {
        $this->options = array_replace_recursive($this->getOptions(), $options);

        return $this;
    }

    /**
     * @param int $curlOpt CURLOPT
     * @param mixed $value
     * 
     * @return static
     */
    public function setOption($curlOpt, $value)
    {
        Arrays::set($this->options, $curlOpt, $value);

        return $this;
    }

    /**
     * @param array $options
     * 
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string|int $curlOpt CURLOPT
     * @param mixed|null $default
     * 
     * @return mixed|null
     */
    public function getOption($curlOpt, $default = null)
    {
        return Arrays::get($this->options, $curlOpt, $default);
    }

    /**
     * @param int $curlOpt CURLOPT
     * @param mixed $value
     * 
     * @return static
     */
    public function setCurlMultiOption($curlOpt, $value)
    {
        return $this->setOption("curl_multi_options.{$curlOpt}", $value);
    }

    /**
     * @param mixed|[] $default
     * 
     * @return array
     */
    public function getCurlMultiOptions($default = [])
    {
        return $this->getOption("curl_multi_options", $default);
    }

    /**
     * @param int $curlOpt CURLOPT
     * @param mixed|[] $default
     * 
     * @return array
     */
    public function getCurlMultiOption($curlOpt, $default = [])
    {
        return $this->getOption("curl_multi_options.{$curlOpt}", $default);
    }

    /**
     * @param int $curlOpt CURLOPT
     * @param mixed $value
     * 
     * @return static
     */
    public function setCurlOption($curlOpt, $value)
    {
        return $this->setOption("curl_options.{$curlOpt}", $value);
    }

    /**
     * @param mixed|[] $default
     * 
     * @return array
     */
    public function getCurlOptions($default = [])
    {
        return $this->getOption("curl_options", $default);
    }

    /**
     * @param int $curlOpt CURLOPT
     * @param mixed|[] $default
     * 
     * @return array
     */
    public function getCurlOption($curlOpt, $default = [])
    {
        return $this->getOption("curl_options.{$curlOpt}", $default);
    }

    /**
     * @param bool|true $default
     * 
     * @return bool|true
     */
    public function getResponseOptionSort($default = true)
    {
        return $this->getOption("response.sort", $default);
    }

    /**
     * @param float $default
     * 
     * @return float
     */
    public function getTimeOut($default = 100)
    {
        return $this->getOption("timeout", $default);
    }

    /**
     * @return callback|\Closure
     */
    public function getRuntimeRejected()
    {
        return $this->getOption("runtimeRejected");
    }

    /**
     * @return callback|\Closure
     */
    public function getFulfilled()
    {
        return $this->getOption("fulfilled");
    }

    /**
     * @return callback|\Closure
     */
    public function getRejected()
    {
        return $this->getOption("rejected");
    }

    /**
     * @param callable|array|null $pool
     * @param array $options
     * 
     * @return array
     */
    public function pool($pool = null, array $options = [])
    {
        // init
        $this->setOptions($options)->newMultiHandle()->init();

        if (is_callable($pool)) {
            $pool = $pool($this);
        }

        if (is_null($pool)) {
            $pool = $this->pool;
        }

        $response = $this->curlMultiOptions(
            $this->getCurlMultiOptions()
        )->addHandle($pool)->mulitExec()->response();

        if ($this->getResponseOptionSort()) {
            $response = array_replace($this->pool, $response);
        }

        return $response;
    }

    /**
     * @param array $options
     * 
     * @return static
     */
    protected function curlMultiOptions(array $options = [])
    {
        foreach ($options as $key => $option) {
            $this->handle->setOpt($key, $option);
        }

        return $this;
    }

    /**
     * @param array $pool
     * 
     * @return static
     */
    protected function addHandle($pool)
    {
        foreach ($pool as $client) {
            $client->setOptions(
                $this->getCurlOptions()
            );

            $this->getHandle()->addHandle($client);

            $client->close();
        }

        return $this;
    }

    /**
     * @return static
     */
    protected function mulitExec()
    {
        $handle = $this->getHandle();

        $runtimeRejected = $this->getRuntimeRejected();

        $active = null;

        do {
            if ($active && $handle->select($this->getTimeOut()) === -1) {
                $exceptionStr = ($mrc = $handle->errorno()) ? $handle->error($mrc) : 'system select failed';

                $rejected = $runtimeRejected(new CurlMultiExecutionException($exceptionStr));

                if ($rejected instanceof \Exception) {
                    throw $rejected;
                }
                // Perform a usleep if a select returns -1.
                // See: https://bugs.php.net/bug.php?id=61141
                usleep($this->getTimeOut());
            }

            while ($handle->exec($active) === CURLM_CALL_MULTI_PERFORM);
        } while ($active);

        return $this;
    }

    /**
     * @return array
     */
    protected function clientCurlPool()
    {
        $pool = [];

        foreach ($this->pool as $key => $client) {
            $pool[$key] = $client->getCurlHandle();
        }

        return $pool;
    }

    /**
     * @param \Closure|callback $fulfilled
     * @param \Closure|callback $rejected
     * 
     * @return array
     */
    protected function clientHandle($fulfilled, $rejected)
    {
        $handle = $this->getHandle();

        $client = $this->client;
        // curl pool
        $pool = $this->clientCurlPool();

        while ($done = $handle->getInfo()) {
            // pool key
            $key = array_search($done['handle'], $pool);

            $client = $client->getHandle()->setCurlHandle($done['handle']);

            $handle->removeHandle($client);

            if ($errno = $client->errno()) {
                $response[$key] = $rejected(new CurlExecutionException($client->error(), $errno), $key);
            } else {
                $response[$key] = $fulfilled(new Response($handle->content($client), $client->getInfo()), $key);
            }
        }

        return $response;
    }

    /**
     * @return array
     */
    protected function response()
    {
        $fulfilled = $this->getFulfilled();

        $rejected = $this->getRejected();

        return $this->clientHandle($fulfilled, $rejected);
    }

    /**
     * @return Client
     */
    public function newClient()
    {
        return $this->client = new Client;
    }

    /**
     * Add a request to the pool with a key.
     *
     * @param  string  $key
     * 
     * @return Client
     */
    public function as(string $key)
    {
        return $this->pool[$key] = $this->asyncRequest();
    }

    /**
     * Retrieve a new async pending request.
     *
     * @return Client
     */
    protected function asyncRequest()
    {
        return $this->newClient()->async();
    }

    /**
     * Retrieve the requests in the pool.
     *
     * @return array
     */
    public function getRequests()
    {
        return $this->pool;
    }

    /**
     * @param string $method
     * @param array $arguments
     * 
     * @return mixed
     */
    // public function __call(string $method, array $arguments)
    // {
    //     return $this->pool[] = $this->asyncRequest()->$method(...$arguments);
    // }

    /**
     * destruct
     */
    public function __destruct()
    {
        // A Pool that's constructed but never actually used to run pool()
        // (newMultiHandle() is only called from within pool()) has a null
        // $handle — matches the same null-safe pattern CurlHandle::close()/
        // CurlMultiHandle::close() already use elsewhere in this package.
        $this->getHandle() && $this->getHandle()->close();
    }
}
