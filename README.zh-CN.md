<p align="right">
  <a href="README.md">English</a> | <strong>中文文档</strong>
</p>

# SuperKernel HTTP Message

`super-kernel/http-message` 提供 SuperKernel 的 PSR-7 消息、URI、流和上传文件，并让服务能够访问当前作用域中的请求和响应。

## 安装

```bash
composer require super-kernel/http-message
```

本包除 PHP 外没有其他要求，并提供 `psr/http-message-implementation` 2.0。它的消息对象无需服务器、也无需构建即可使用；注入当前请求或响应需要 `super-kernel/di`，并且需要有代码保存这些消息，`super-kernel/http-server` 会为它处理的每个请求这样做。

## 提供的能力

- `Request`、`ServerRequest`、`Response` 和 `Uri`：带有公开构造函数的不可变 PSR-7 对象。
- 基于字符串、原生资源、文件、分块可迭代对象、读取回调的流，以及另一个流的只读视图。
- `UploadedFile`：基于流、临时文件路径或失败的上传。
- `RequestContext` 和 `ResponseContext`：保存当前协程或进程的消息。
- 作为容器服务的 `ServerRequestInterface` 和 `ResponseInterface`，它们始终作用于当前消息。

它不提供：

- `psr/http-factory` 工厂；
- 路由、中间件或控制器调用；
- 从原生服务器请求的转换或响应的发送；
- 协议错误的恢复；
- 把数组或对象序列化为消息体。

## 快速开始

创建一个请求和一个响应。将脚本保存为 `bin/messages.php`：

```php
<?php

declare(strict_types=1);

use SuperKernel\HttpMessage\Request;
use SuperKernel\HttpMessage\Response;
use SuperKernel\HttpMessage\Stream\StringStream;

require dirname(__DIR__) . '/vendor/autoload.php';

$request = new Request('GET', 'https://Example.test:443/widgets?limit=10');
$json = $request->withHeader('Accept', 'application/json');

echo $request->getHeaderLine('Host'), ' ', $request->getRequestTarget(), PHP_EOL;
echo $request->hasHeader('Accept') ? 'changed' : 'unchanged', PHP_EOL;
echo $json->getHeaderLine('accept'), PHP_EOL;

$response = new Response(
    statusCode: 404,
    headers: [
        'Content-Type' => 'application/json',
    ],
    body: new StringStream('{"error":"not found"}'),
);

echo $response->getStatusCode(), ' ', $response->getReasonPhrase(), ' ', $response->getBody(), PHP_EOL;
```

运行脚本：

```bash
php bin/messages.php
```

它会打印：

```text
example.test /widgets?limit=10
unchanged
application/json
404 Not Found {"error":"not found"}
```

## 公开接口

| 名称 | 类别 | 用途 |
| --- | --- | --- |
| `SuperKernel\HttpMessage\Request` | 类 | 发出的请求；未提供 `Host` 头时从 URI 推导。 |
| `SuperKernel\HttpMessage\ServerRequest` | 类 | 接收到的请求，带有服务器、Cookie、查询、上传、解析后的消息体和属性数据。 |
| `SuperKernel\HttpMessage\Response` | 类 | 响应；未提供原因短语时使用标准原因短语。 |
| `SuperKernel\HttpMessage\Uri` | 类 | 从字符串解析的 URI。 |
| `SuperKernel\HttpMessage\Message` | 类 | 请求和响应的抽象基类。 |
| `SuperKernel\HttpMessage\Stream\StringStream` | 类 | 基于临时流中字符串的流。 |
| `SuperKernel\HttpMessage\Stream\ResourceStream` | 类 | 基于已打开的原生流资源的流。 |
| `SuperKernel\HttpMessage\Stream\FileStream` | 类 | 基于文件的流，默认只读（`rb`）。 |
| `SuperKernel\HttpMessage\Stream\IterableStream` | 类 | 基于字符串分块可迭代对象的只进流。 |
| `SuperKernel\HttpMessage\Stream\CallbackStream` | 类 | 基于读取回调的只进流。 |
| `SuperKernel\HttpMessage\Stream\ReadOnlyStream` | 类 | 拒绝写入的另一个流的视图。 |
| `SuperKernel\HttpMessage\UploadedFile` | 类 | 来自流、临时路径或失败上传的上传文件。 |
| `SuperKernel\HttpMessage\Context\RequestContext` | 类 | 保存、读取和清除当前服务器请求。 |
| `SuperKernel\HttpMessage\Context\ResponseContext` | 类 | 保存、读取和清除当前响应。 |
| `Psr\Http\Message\ServerRequestInterface` | 接口 | 注入为一个服务，其调用作用于当前服务器请求。 |
| `Psr\Http\Message\ResponseInterface` | 接口 | 注入为一个服务，其调用作用于当前响应。 |

## 需要了解的行为

- `with*()` 方法返回修改后的副本，原对象保持不变。副本共享消息体流，因此通过其中一个读取消息体会移动另一个所见的游标。
- 无效输入会抛出 `InvalidArgumentException`，例如 `Invalid HTTP status code 600.` 或 `Invalid HTTP header name Bad Name.`；流或上传操作失败会抛出 `RuntimeException`。
- 流拥有它持有的原生资源：`close()` 关闭资源，`detach()` 把资源交给调用方。此后，流对每个需要资源的操作都会抛出 `RuntimeException`；把它转换为字符串返回 `''`，且从不抛出异常。
- 可迭代流和回调流的 `read()` 在数据源产生字节后立即返回，因此返回的字节可能少于请求的数量。
- 成功调用 `moveTo()` 之后，`getStream()` 和再次调用 `moveTo()` 会抛出 `The uploaded file has already been moved.`
- 没有保存请求时调用注入的 `ServerRequestInterface` 会抛出 `The current HTTP request context has not been initialized.`，注入的 `ResponseInterface` 会抛出 `The current HTTP response context has not been initialized.` 这些服务从构建元数据中发现：安装本包后请运行 `skernel build`，或者用会先构建的 `skernel run` 启动。

## 文档

开发者文档介绍了每一种流、上传、URI 规范化、当前请求服务以及错误消息：[SuperKernel HTTP Message](https://github.com/super-kernel/.github/blob/main/docs/components/http-message.zh-CN.md)。

## 许可证

基于 MIT 许可证发布，详见 [LICENSE](LICENSE)。
