<?php

namespace Wilkques\Http;

use Wilkques\Helpers\Arrays;
use Wilkques\Http\Contracts\ClientInterface;
use Wilkques\Http\Exceptions\CurlExecutionException;

class Client implements ClientInterface
{
    /** @var array */
    private $headers = [];

    /** @var CurlHandle */
    private $handle;

    /** @var array */
    private $options = [
        'curl_options' => [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS | CURLPROTO_HTTP,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
        ],
    ];

    /** @var array */
    private $files = [];

    /** @var bool */
    protected $async = false;

    /**
     * @param CurlHandle|null $handle
     */
    public function __construct(?CurlHandle $handle = null)
    {
        $handle && $this->setHandle($handle);

        $this->asJson()->acceptJson();
    }

    /**
     * @param string $url
     * 
     * @return static
     */
    public function setUrl(string $url)
    {
        return $this->setCurlOption(CURLOPT_URL, $url);
    }

    /**
     * @return static
     */
    public function async()
    {
        $this->async = true;

        return $this;
    }

    /**
     * @param CurlHandle $handle
     * 
     * @return static
     */
    public function setHandle(CurlHandle $handle)
    {
        $this->handle = $handle;

        return $this;
    }

    /**
     * @return CurlHandle
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * @param string $channelToken
     * 
     * @return static
     */
    public function withToken(string $token, string $type = 'Bearer')
    {
        return $this->setHeader('Authorization', "{$type} {$token}");
    }

    /**
     * @param string $url
     * @param array|[] $data
     * 
     * @return string
     */
    protected function urlBuilder(string $url, $data = [])
    {
        if (!empty($data)) {
            $url .= '?' . Arrays::query($data);
        }

        return $url;
    }

    /**
     * Sends GET request to API.
     *
     * @param string $url Request URL.
     * @param array[] $query Request body
     * 
     * @return Response Response of API request.
     * 
     * @throws CurlExecutionException
     */
    public function get(string $url, array $query = [])
    {
        return $this->methodGet()->sendRequest('GET', $this->urlBuilder($url, $query));
    }

    /**
     * Sends PUT request to API.
     *
     * @param string $url Request URL.
     * @param array[] $data Request body or resource path.
     * @param array|null $query
     * 
     * @return Response Response of API request.
     * 
     * @throws CurlExecutionException
     */
    public function put(string $url, array $data = [], ?array $query = null)
    {
        return $this->sendRequest('PUT', $this->urlBuilder($url, $query), $data);
    }

    /**
     * Sends PATCH request to API.
     *
     * @param string $url Request URL.
     * @param array[] $data Request body or resource path.
     * @param array|null $query
     * 
     * @return Response Response of API request.
     * 
     * @throws CurlExecutionException
     */
    public function patch(string $url, array $data = [], ?array $query = null)
    {
        return $this->sendRequest('PATCH', $this->urlBuilder($url, $query), $data);
    }


    /**
     * Sends POST request to API.
     *
     * @param string $url Request URL.
     * @param array[] $data Request body or resource path.
     * @param array|null $query
     * 
     * @return Response Response of API request.
     * 
     * @throws CurlExecutionException
     */
    public function post(string $url, array $data = [], ?array $query = null)
    {
        return $this->methodPost()->sendRequest('POST', $this->urlBuilder($url, $query), $data);
    }

    /**
     * Sends DELETE request to API.
     *
     * @param string $url Request URL.
     * @param array[] $query
     * 
     * @return Response Response of API request.
     * 
     * @throws CurlExecutionException
     */
    public function delete(string $url, array $query = [])
    {
        return $this->sendRequest('DELETE', $this->urlBuilder($url, $query));
    }

    /**
     * @param array $headers
     * 
     * @return static
     */
    public function withHeaders(array $headers)
    {
        $this->headers = array_replace_recursive($this->getHeaders(), $headers);

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     * 
     * @return array
     */
    public function setHeader(string $key, $value)
    {
        return $this->withHeaders([$key => $value]);
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param string $key
     * 
     * @return string
     */
    public function getHeader(string $key)
    {
        return Arrays::get($this->getHeaders(), $key);
    }

    /**
     * Specify the request's content type.
     *
     * @param  string  $contentType
     * 
     * @return static
     */
    public function contentType(string $contentType)
    {
        return $this->setHeader('Content-Type', $contentType);
    }

    /**
     * @return static
     */
    public function asForm()
    {
        return $this->contentType('application/x-www-form-urlencoded; charset=utf-8');
    }

    /**
     * @return static
     */
    public function asJson()
    {
        return $this->contentType('application/json; charset=utf-8');
    }

    /**
     * @return static
     */
    public function asMultipart()
    {
        // No charset/boundary appended here: curl always generates and
        // sends its own real boundary parameter for a multipart body (any
        // POSTFIELDS array containing a CURLFile), appending it to
        // whatever Content-Type is already set. Appending a hand-rolled
        // boundary here produced a Content-Type whose declared boundary
        // never matched the one curl actually used to encode the body, so
        // receiving servers could never correctly parse the multipart
        // payload (confirmed: $_POST/$_FILES both came back empty).
        return $this->contentType('multipart/form-data');
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
     * @param int $key
     * @param mixed $value
     * 
     * @return static
     */
    public function setOption($key, $value)
    {
        Arrays::set($this->options, $key, $value);

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string|int $key
     * @param mixed|null $default
     * 
     * @return mixed|null
     */
    public function getOption($key, $default = null)
    {
        return Arrays::get($this->options, $key, $default);
    }

    /**
     * @param string|int $curlOpt
     * @param mixed $value
     * 
     * @return static
     */
    public function setCurlOption($curlOpt, $value)
    {
        return $this->setOption("curl_options.{$curlOpt}", $value);
    }

    /**
     * @return array
     */
    public function getCurlOptions()
    {
        return $this->getOption("curl_options");
    }

    /**
     * @param string|int $curlOpt
     * 
     * @return mixed|null
     */
    public function getCurlOption($curlOpt)
    {
        return $this->getOption("curl_options.{$curlOpt}");
    }

    /**
     * @return static
     */
    public function acceptJson()
    {
        return $this->setHeader('Accept', 'application/json; charset=utf-8');
    }

    /**
     * @param string $method
     * 
     * @return static
     */
    private function httpMethod(string $method)
    {
        return $this->setCurlOption(CURLOPT_CUSTOMREQUEST, $method);
    }

    /**
     * @return static
     */
    private function methodGet()
    {
        return $this->setCurlOption(CURLOPT_HTTPGET, true);
    }

    /**
     * @return static
     */
    private function methodPost()
    {
        return $this->setCurlOption(CURLOPT_POST, true);
    }

    /**
     * @return static
     */
    private function methodPut()
    {
        return $this->setCurlOption(CURLOPT_PUT, true);
    }

    /**
     * @return static
     */
    private function buildHeaders()
    {
        $headers = $this->getHeaders();

        return $this->setCurlOption(CURLOPT_HTTPHEADER, Arrays::map($headers, function ($item, $index) {
            if (is_string($index)) {
                return "{$index}: {$item}";
            }

            return $item;
        }));
    }

    /**
     * @return static
     */
    private function noContentLength()
    {
        return $this->setHeader('Content-Length', '0');
    }

    /**
     * @param string|array|null $fields
     * 
     * @return static
     */
    private function postFields($fields)
    {
        return $this->setCurlOption(CURLOPT_POSTFIELDS, $this->fieldsEncode($fields));
    }

    /**
     * @param string|array|null $fields
     * 
     * @return static
     */
    protected function fieldsEncode($fields)
    {
        switch ($this->getHeader('Content-Type')) {
            case 'application/x-www-form-urlencoded; charset=utf-8':
                return http_build_query($fields);
                break;
            // case 'application/json; charset=utf-8':
            //     $fields = json_encode($fields);
            //     break;
            default:
                return $fields;
                break;
        }
    }

    /**
     * @param array|string $name
     * @param string|null $filePath
     * @param string|null $mimeType
     * @param string|null $reName
     * 
     * @return static
     */
    public function attach($name, string $filePath = '', ?string $mimeType = null, ?string $reName = null)
    {
        if (is_array($name)) {
            foreach ($name as $file) {
                $this->attach(...$file);
            }

            return $this;
        }

        // curl always sends the body as multipart when any CURLFile is
        // present in CURLOPT_POSTFIELDS, regardless of what Content-Type
        // says — declaring anything other than multipart/form-data here
        // (the default from the constructor's asJson() is otherwise left
        // in place) meant the header lied about the actual body encoding,
        // so receiving servers never parsed the upload correctly.
        $this->asMultipart();

        $mimeType = $mimeType ?? mime_content_type($filePath);

        $fileName = $reName ?? pathinfo($filePath)['basename'];

        return $this->setFiles($name, $this->createFile($filePath, $mimeType, $fileName));
    }

    /**
     * Only send one File
     * 
     * @param string $filePath
     * 
     * @return static
     */
    public function attachUploadFile(string $filePath)
    {
        return $this->methodPut()->setCurlOption(CURLOPT_UPLOAD, true)
            ->setCurlOption(CURLOPT_INFILE, fopen($filePath, 'rb'))
            ->setCurlOption(CURLOPT_INFILESIZE, filesize($filePath));
    }

    /**
     * @return static
     */
    private function fileClose()
    {
        if ($cURLFile = $this->getCurlOption(CURLOPT_INFILE)) {
            fclose($cURLFile);
        }

        // CURLFile (used by getFiles()/attach()) has no getStream() method
        // and nothing to close here: curl opens/reads/closes the
        // underlying file itself. This unconditionally fataled ("Call to
        // undefined method CURLFile::getStream()") on every attach() use,
        // in __destruct(), regardless of PHP version.

        return $this;
    }

    /**
     * @param string $key
     * @param \CURLFile $cURLFile
     * 
     * @return static
     */
    public function setFiles(string $key, \CURLFile $cURLFile)
    {
        $this->files[$key] = $cURLFile;

        return $this;
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param string|array|null $requestBody
     * 
     * @return static cUrl options
     */
    private function buildRequestBody($requestBody = null)
    {
        if (is_null($requestBody)) {
            return $this->noContentLength();
        }

        return $this->postFields(array_replace_recursive($requestBody, $this->getFiles()));
    }

    /**
     * @param string $method
     * @param string $url
     * @param string|array|null $requestBody
     * 
     * @throws CurlExecutionException
     * 
     * @return Response
     */
    private function sendRequest(string $method, string $url, $requestBody = null)
    {
        $this->setUrl($url)
            ->httpMethod($method)
            ->buildHeaders()
            ->buildRequestBody($requestBody)
            ->init()
            ->setoptArray(
                $this->getCurlOptions()
            );

        if ($this->async) {
            return $this;
        }

        return new Response($this->execCurl(), $this->getInfo());
    }

    /**
     * @return string
     */
    protected function execCurl()
    {
        $result = $this->exec();

        if ($this->errno()) throw new CurlExecutionException($this->error(), $this->errno());

        return $result;
    }

    /**
     * @return CurlHandle
     */
    public function newCurl()
    {
        return $this->getHandle() ?: $this->setHandle(new CurlHandle)->setClient($this);
    }

    /**
     * @param string $method
     * @param array $arguments
     * 
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        return $this->newCurl()->$method(...$arguments);
    }

    /**
     * @param string $method
     * @param array $arguments
     * 
     * @return mixed
     */
    public static function __callStatic($method, $arguments)
    {
        return (new static)->$method(...$arguments);
    }

    /**
     * Client destruct.
     */
    public function __destruct()
    {
        $this->fileClose()->close();
    }
}
