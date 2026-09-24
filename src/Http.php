<?php

namespace Wilkques\Http;

use Wilkques\Http\Client;

/**
 * @method static \Wilkques\Http\Client asForm() add header Content-Type application/x-www-form-urlencoded; charset=utf-8
 * @method static \Wilkques\Http\Client asJson() add header Content-Type application/json; charset=utf-8
 * @method static \Wilkques\Http\Client asMultipart() add header Content-Type multipart/form-data; charset=utf-8
 * @method static \Wilkques\Http\Client withHeaders(array $headers) add headers
 * @method static \Wilkques\Http\Client withToken(string $token, string $type = 'Bearer') add Authorization token
 * @method static \Wilkques\Http\Client attach(string|array $name, ?string $filePath = null, ?string $mimeType = null, ?string $reName = null) file upload
 * @method static \Wilkques\Http\Client attachUploadFile(string $filePath) Only send one File.
 * @method static \Wilkques\Http\Client contentType(string $contentType) custom Content-Type
 * @method static \Wilkques\Http\Response get(string $url, array $data = []) http method get
 * @method static \Wilkques\Http\Response post(string $url, array $data, array $query = null) http method post
 * @method static \Wilkques\Http\Response put(string $url, array $data = [], array $query = null)
 * @method static \Wilkques\Http\Response patch(string $url, array $data = [], array $query = null)
 * @method static \Wilkques\Http\Response delete(string $url, array $query = null)
 */
class Http
{
    /** @var Client */
    protected $client;

    /** @var Pool */
    protected $pool;

    /**
     * @param Client|null $client
     * @param Pool|null $pool
     */
    public function __construct(Client $client = null, Pool $pool = null)
    {
        $this->setClient($client)->setPool($pool);
    }

    /**
     * @param Client|null $client
     * @param Pool|null $pool
     *
     * @return static
     */
    public static function make(Client $client = null, Pool $pool = null)
    {
        return new static($client, $pool);
    }

    /**
     * @return static
     */
    public function setClient(Client $client = null)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return Client
     */
    public function newClient()
    {
        if (!$this->getClient()) {
            $this->setClient(new Client);
        }

        return $this->getClient();
    }

    /**
     * @return static
     */
    public function setPool(Pool $pool = null)
    {
        $this->pool = $pool;

        return $this;
    }

    /**
     * @return Pool
     */
    public function getPool()
    {
        return $this->pool;
    }

    /**
     * @return Pool
     */
    public function newPool()
    {
        if (!$this->getPool()) {
            $this->setPool(new Pool);
        }

        return $this->getPool();
    }

    /**
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call($method, array $arguments)
    {
        if (method_exists($this->newClient(), $method)) {
            return call_user_func_array(array($this->newClient(), $method), $arguments);
        }

        return call_user_func_array(array($this->newPool(), $method), $arguments);
    }

    /**
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     */
    public static function __callStatic($method, $arguments)
    {
        return call_user_func_array(array(new static, $method), $arguments);
    }
}
