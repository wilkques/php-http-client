# Http Client

[![Latest Stable Version](https://poser.pugx.org/wilkques/http-client/v/stable)](https://packagist.org/packages/wilkques/http-client)
[![License](https://poser.pugx.org/wilkques/http-client/license)](https://packagist.org/packages/wilkques/http-client)

[English](README.md) | 繁體中文

## 需求

- PHP >= 5.3（已在 5.3、7.4、8.3 測試過）
- ext-curl

## 如何開始

````
composer require wilkques/http-client
````
## 使用方式

```php
use Wilkques\Http\Http;
```

## 方法

1. `setCurlOption` 設定 PHP CURL 選項
    ```php
    $response = Http::setCurlOption(<CURL OPTION>, <Value>);

        // 範例

    $response = Http::setCurlOption(CURLOPT_TIMEOUT, 100);
    ```

1. `withHeaders`

    ```php
    $response = Http::withHeaders([ ... ]); // 加入 header

    // 範例

    $response = Http::withHeaders([
        'Accept' => 'application/json; charset=utf-8'
    ]);
    ```

1. `asForm`

    ```php
    $response = Http::asForm(); // 加上 application/x-www-form-urlencoded header
    ```

1. `asJson`

    ```php
    $response = Http::asJson(); // 加上 application/json header
    ```

1. `asMultipart`

    ```php
    $response = Http::asMultipart(); // 加上 multipart/form-data header
    ```

1. `attach`

    ```php
    $response = Http::attach('<post key name>', '<file path>', '<file type>', '<file name>'); // 附加檔案
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
    $response->status(); // 取得 http 狀態碼
    ```

1. `body`

    ```php
    $response->body(); // 取得 body
    ```

1. `json`

    ```php
    $response->json(); // 取得 json_decode 過的 body
    ```

1. `headers`

    ```php
    $response->headers(); // 取得所有 headers
    ```

1. `header`

    ```php
    $response->header('<key>'); // 取得單一 header
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
    $response->throwException(); // 丟出例外
    
    // 或

    $response->throwException(new \Exception('<message>', '<code>'));

    // 或

    $response->throwException(function ($response, $exception) {
        // code
        // 回傳例外
    });
    ```

1. `pool`

    ```php
    $response = \Wilkques\Http\Http::Pool(function (\Wilkques\Http\Pool $pool) {
        return [
            $pool->get('http://example.com/get', ['abc' => 123]),
            $pool->post('http://example.com/post', ['def' => 456]),
            $pool->alias('get')->get('http://example.com/get', ['ghi' => 789]),
            $pool->alias('post')->post('http://example.com/post', ['jkl' => 012]),
        ];
    }, [
        'response'  => [
            'sort'  => true, // 是否對 response 排序，預設 true
        ],
        'timeout'   => 100, // timeout 微秒，建議 < 1 秒，預設 100
        // 成功
        'fulfilled' => function (\Wilkques\Http\Response $response, $index) {
            var_dump($index); // 陣列索引
            var_dump($response); // \Wilkques\Http\Response

            return $response;
        },
        // 失敗
        'rejected' => function (\Wilkques\Http\Exceptions\CurlExecutionException $exception, $index) {
            var_dump($index); // 陣列索引
            var_dump($exception); // \Wilkques\Http\Exceptions\CurlExecutionException

            return $response;
        },
        'options'   => [
            // curl_multi_setopt 選項與值 ...
        ]
    ]);

    // 輸出
    // array(
    //    '0'       => Wilkques\Http\Response...,
    //    '1'       => Wilkques\Http\Response...,
    //    'get'     => Wilkques\Http\Response...,
    //    'post'    => Wilkques\Http\Response...,
    // )
    var_dump($response);

    $response[0]->failed();

    $response[1]->successful();

    // 其他方法同上 ...
    ```
