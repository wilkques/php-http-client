# Http Client

[![Latest Stable Version](https://poser.pugx.org/wilkques/http-client/v/stable)](https://packagist.org/packages/wilkques/http-client)
[![License](https://poser.pugx.org/wilkques/http-client/license)](https://packagist.org/packages/wilkques/http-client)

English | [繁體中文](README_ZH.md)

## How to start

````
composer require wilkques/http-client
````
## How to use

```php
use Wilkques\Http\Http;
```

## Methods

1. `setCurlOption` PHP CURL Setting
    ```php
    $response = Http::setCurlOption(<CURL OPTION>, <Value>);

        // Ex

    $response = Http::setCurlOption(CURLOPT_TIMEOUT, 100);
    ```

1. `withHeaders`

    ```php
    $response = Http::withHeaders([ ... ]); // add header

    // Ex

    $response = Http::withHeaders([
        'Accept' => 'application/json; charset=utf-8'
    ]);
    ```

1. `asForm`

    ```php
    $response = Http::asForm(); // add header application/x-www-form-urlencoded
    ```

1. `asJson`

    ```php
    $response = Http::asJson(); // add header application/json
    ```

1. `asMultipart`

    ```php
    $response = Http::asMultipart(); // add header multipart/form-data
    ```

1. `attach`

    ```php
    $response = Http::attach('<post key name>', '<file path>', '<file type>', '<file name>'); // add file
    ```

1. `get`

    ```php
    $response = Http::get('<url>', [ ... ]); // Http method get
    ```

1. `post`

    ```php
    $response = Http::post('<url>', [ ... ]) // Http method post
    ```

1. `put`

    ```php
    $response = Http::put('<url>', [ ... ]) // Http method put
    ```

1. `patch`

    ```php
    $response = Http::patch('<url>', [ ... ]) // Http method patch
    ```

1. `delete`

    ```php
    $response = Http::delete('<url>', [ ... ]) // Http method delete
    ```

1. `status`

    ```php
    $response->status(); // get http status code
    ```

1. `body`

    ```php
    $response->body(); // get body
    ```

1. `json`

    ```php
    $response->json(); // get json_decode body
    ```

1. `headers`

    ```php
    $response->headers(); //get headers
    ```

1. `header`

    ```php
    $response->header('<key>'); // get header
    ```

1. `ok`

    ```php
    $response->ok(); // bool
    ```

1. `redirect`

    ```php
    $response->redirect(); // bool
    ```

1. `successful`

    ```php
    $response->successful(); // bool
    ```

1. `failed`

    ```php
    $response->failed(); // bool
    ```

1. `clientError`

    ```php
    $response->clientError(); // bool
    ```

1. `serverError`

    ```php
    $response->serverError(); // bool
    ```

1. `throw`

    ```php
    $response->throw(); // throw exception
    
    // or
    
    $response->throw(new \Exception('<message>', '<code>'));

    // or

    $response->throw(function ($response, $exception) {
        // code
        // return exception
    });
    ```

1. `pool`

    ```php
    $response = \Wilkques\Http\Http::Pool(function (\Wilkques\Http\Pool $pool) {
        return [
            $pool->get('http://example.com/get', ['abc' => 123]),
            $pool->post('http://example.com/post', ['def' => 456]),
            $pool->as('get')->get('http://example.com/get', ['ghi' => 789]),
            $pool->as('post')->post('http://example.com/post', ['jkl' => 012]),
        ];
    }, [
        'response'  => [
            'sort'  => true, // response sort, default true
        ],
        'timeout'   => 100, // timeout microseconds suggest < 1 sec, default 100
        // success
        'fulfilled' => function (\Wilkques\Http\Response $response, $index) {
            var_dump($index); // array index
            var_dump($response); // \Wilkques\Http\Response

            return $response;
        },
        // fail
        'rejected' => function (\Wilkques\Http\Exceptions\CurlExecutionException $exception, $index) {
            var_dump($index); // array index
            var_dump($exception); // \Wilkques\Http\Exceptions\CurlExecutionException

            return $response;
        },
        'options'   => [
            // curl_multi_setopt option & value ...
        ]
    ]);

    // output
    // array(
    //    '0'       => Wilkques\Http\Response...,
    //    '1'       => Wilkques\Http\Response...,
    //    'get'     => Wilkques\Http\Response...,
    //    'post'    => Wilkques\Http\Response...,
    // )
    var_dump($response);

    $response[0]->failed();

    $response[1]->successful();

    // etc ...
    ```