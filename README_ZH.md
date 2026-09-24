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

1. `withBasicAuth`

    ```php
    $response = Http::withBasicAuth('<username>', '<password>'); // 加上 Basic Authorization header
    ```

1. `withDigestAuth`

    ```php
    $response = Http::withDigestAuth('<username>', '<password>'); // 使用 HTTP Digest 驗證
    ```

1. `withCookies`

    ```php
    $response = Http::withCookies(['session' => '<value>']); // 加上 Cookie header
    ```

1. `withUserAgent`

    ```php
    $response = Http::withUserAgent('<user agent>'); // 設定 User-Agent header
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

1. `timeout`

    ```php
    $response = Http::timeout(30); // 整個請求的時間上限（秒）
    ```

1. `connectTimeout`

    ```php
    $response = Http::connectTimeout(10); // 建立連線的時間上限（秒）
    ```

1. `withoutRedirecting`

    ```php
    $response = Http::withoutRedirecting()->get('<url>'); // 遇到第一個 3xx 就停下來，不自動跟隨
    ```

1. `maxRedirects`

    ```php
    $response = Http::maxRedirects(3)->get('<url>'); // 預設會跟隨重新導向（最多 5 次），這個方法可以改上限
    ```

1. `retry`

    ```php
    // 只有在傳輸層失敗（DNS／連線／timeout）才會重試，
    // HTTP 錯誤狀態碼（4xx/5xx）不會觸發重試
    $response = Http::retry(3, 100)->get('<url>'); // 最多重試 3 次，每次間隔 100ms
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

1. `throwIf` / `throwUnless`

    ```php
    // 不管 failed()/successful() 的結果，純粹依照給定的條件
    //（bool，或 callable(Response): bool）決定要不要丟例外
    $response->throwIf($response->header('X-Foo') === null);

    $response->throwUnless(function ($response) {
        return $response->header('X-Foo') !== null;
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
