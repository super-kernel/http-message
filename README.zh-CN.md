<p align="right">
  <a href="README.md">English</a> | <strong>中文文档</strong>
</p>

# SuperKernel HTTP Message

`super-kernel/http-message` 为 SuperKernel 提供 PSR-7 消息对象和请求作用域内的消息访问。其消息对象实现 `psr/http-message` 2.0，不依赖原生服务器。

本包提供：

- `Message`，共享的不可变消息基类；
- `Request` 和 `ServerRequest`；
- `Response`；
- `Uri`；
- 字符串、原生资源、文件、可迭代、回调和只读的流实现；
- `UploadedFile`；
- `Context\RequestContext` 和 `Context\ResponseContext`，当前消息可以作为 PSR-7 接口注入。

## 基本用法

```php
use SuperKernel\HttpMessage\Request;
use SuperKernel\HttpMessage\Response;
use SuperKernel\HttpMessage\Stream\StringStream;

$request = new Request('GET', 'https://example.test/widgets?limit=10');
$request = $request->withHeader('Accept', 'application/json');

$response = new Response(
    statusCode: 200,
    headers: [
        'Content-Type' => 'application/json',
    ],
    body: new StringStream('{"items":[]}'),
);
```

消息、请求、响应和 URI 使用 PSR-7 的写时复制方法。修改后的值以新对象返回，原对象保持不变。消息体有意保留 PSR-7 流的身份，因此其读写位置可能会变化。

## 流与上传

针对每种来源场景，通过对应流类的公开构造函数创建：

```php
use SuperKernel\HttpMessage\Stream\FileStream;
use SuperKernel\HttpMessage\Stream\ResourceStream;
use SuperKernel\HttpMessage\Stream\StringStream;

$memory = new StringStream('payload');
$resource = new ResourceStream($nativeResource);
$file = new FileStream('/srv/application/data.json');
```

每个流持有其附着的原生资源。`close()` 关闭资源，`detach()` 把资源转交给调用方。已分离或已关闭的流会拒绝需要资源的操作。

当来源无法自然地用一个原生资源表示时，使用按行为划分的实现：

```php
use SuperKernel\HttpMessage\Stream\CallbackStream;
use SuperKernel\HttpMessage\Stream\IterableStream;
use SuperKernel\HttpMessage\Stream\ReadOnlyStream;

$chunks = new IterableStream([
    'first',
    'second',
]);
$callback = new CallbackStream($reader);
$readOnly = new ReadOnlyStream($memory);
```

可迭代流和回调流是可读、只能向前的，大小未知。可迭代的值必须是字符串；回调读取器接收请求的字节数，返回非空字符串，到达来源末尾时返回 `null`。只要来源产生了任何字节，`read()` 就会立即返回，从不等待凑满请求的长度，因此像事件流这样的慢速生产者会一块一块地到达使用方；比请求更大的块会留在缓冲区中供后续读取。它们的 `detach()` 方法释放生产者并返回 `null`。`ReadOnlyStream` 把读取、定位、元数据、关闭和分离委托给另一个 PSR 流，同时拒绝写入。所有流内容都是字节；数组或对象的序列化不属于流层。

`UploadedFile` 接受一个 `StreamInterface`、一个本地临时路径，或者在上传失败时不接受任何来源。`moveTo()` 成功后，按照 PSR-7 的要求，`getStream()` 和第二次移动都会失败。

## 作用域访问

`RequestContext` 和 `ResponseContext` 以 PSR 接口为键，把当前消息存放在 `SuperKernel\Context\Context` 中。注入 `Psr\Http\Message\ServerRequestInterface` 或 `Psr\Http\Message\ResponseInterface` 即可读取它们：对注入服务的每次调用都会转到当前作用域的消息，所以保留它的共享服务永远不会看到其他请求的状态。注入的服务只暴露 PSR-7 方法，没有魔术转发、上下文访问器或原生传输对象。

## URI 行为

`Uri` 把 scheme 和主机名规范为小写，从渲染出的 authority 中去掉 HTTP 和 HTTPS 的标准端口，保留有效的百分号编码字节，并对无效的组件字节进行百分号编码，且不会重复编码。

## 范围

本包不提供 `psr/http-factory` 工厂、路由、中间件编排、控制器调用、原生请求适配、响应发送、回调注册或协议异常恢复。原生运行时集成由 `super-kernel/http-server` 提供。
