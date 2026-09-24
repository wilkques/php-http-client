<?php

namespace Wilkques\Http;

/**
 * cURL session manager
 */
class CurlHandle
{
    /** @var \CurlHandle */
    private $curlHandle;

    /** @var Client */
    private $client;

    /**
     * @param \CurlHandle|boolean $curlHandle
     * 
     * @return static
     */
    public function setCurlHandle($curlHandle)
    {
        $this->curlHandle = $curlHandle;

        return $this;
    }

    /**
     * @return \CurlHandle|boolean
     */
    public function getCurlHandle()
    {
        return $this->curlHandle;
    }

    /**
     * @return static
     */
    public function init()
    {
        return $this->setCurlHandle(curl_init());
    }

    /**
     * @param Client $curlHTTPClient
     * 
     * @return static
     */
    public function setClient(Client $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return CurlHTTPClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set multiple options for a cURL transfer
     *
     * @param array $options Returns TRUE if all options were successfully set. If an option could not be
     * successfully set, FALSE is immediately returned, ignoring any future options in the options array.
     * @return bool
     */
    public function setoptArray(array $options)
    {
        return curl_setopt_array($this->getCurlHandle(), $options);
    }

    /**
     * Perform a cURL session
     *
     * @return string|bool Returns TRUE on success or FALSE on failure. However, if the CURLOPT_RETURNTRANSFER
     * option is set, it will return the result on success, FALSE on failure.
     */
    public function exec()
    {
        return curl_exec($this->getCurlHandle());
    }

    /**
     * Gets information about the last transfer.
     * 
     * @param int|null $option
     *
     * @return mixed
     */
    public function getInfo($option = null)
    {
        if (!$option) {
            return curl_getinfo($this->getCurlHandle());
        }

        return curl_getinfo($this->getCurlHandle(), $option);
    }

    /**
     * create curl file
     *
     * CURLFile (curl_file_create()) has existed continuously since PHP
     * 5.5 all the way through 8.3+, so this covers every version except
     * PHP 5.3/5.4, which have no object-based upload API at all — the
     * only thing that ever worked there is the legacy "@path;type=..;
     * filename=.." CURLOPT_POSTFIELDS string, which PHP 8.0 later removed
     * entirely. Since the two paths don't overlap on any real PHP
     * version (CURLFile is used everywhere it exists), this needs no
     * further version branching.
     *
     * @param string $filePath
     * @param string $mimeType
     * @param string $fileName
     *
     * @return \CURLFile|string
     */
    public function createFile($filePath, $mimeType, $fileName)
    {
        if (function_exists('curl_file_create')) {
            return curl_file_create($filePath, $mimeType, $fileName);
        }

        return '@' . $filePath . ';type=' . $mimeType . ';filename=' . $fileName;
    }

    /**
     * @return int Returns the error number or 0 (zero) if no error occurred.
     */
    public function errno()
    {
        return curl_errno($this->getCurlHandle());
    }

    /**
     * @return string Returns the error message or '' (the empty string) if no error occurred.
     */
    public function error()
    {
        return curl_error($this->getCurlHandle());
    }

    /**
     * Closes a cURL session and frees all resources. The cURL handle, ch, is also deleted.
     */
    public function close()
    {
        $this->getCurlHandle() && curl_close($this->getCurlHandle());
    }

    public function __call($method, $arguments)
    {
        return call_user_func_array(array($this->getClient(), $method), $arguments);
    }
}
