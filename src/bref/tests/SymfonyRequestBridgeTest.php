<?php

namespace Runtime\Bref\Tests;

use Bref\Context\Context;
use Bref\Event\Http\HttpRequestEvent;
use PHPUnit\Framework\TestCase;
use Runtime\Bref\SymfonyRequestBridge;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SymfonyRequestBridgeTest extends TestCase
{
    public function testClientIpFromForwardedFor()
    {
        $request = SymfonyRequestBridge::convertRequest(new HttpRequestEvent([
            'requestContext' => ['http' => ['method' => 'GET']],
            'multiValueHeaders' => ['x-forwarded-for' => ['98.76.54.32']],
        ]), $this->getContext());
        $this->assertSame('98.76.54.32', $request->getClientIp());
    }

    /**
     * Raw content should only exist when there is no multipart content.
     */
    public function testRawContent()
    {
        // No content type
        $request = SymfonyRequestBridge::convertRequest(new HttpRequestEvent([
            'requestContext' => ['http' => ['method' => 'POST']],
            'body' => '{"foo":"bar"}',
        ]), $this->getContext());
        $this->assertSame('{"foo":"bar"}', $request->getContent());

        $request = SymfonyRequestBridge::convertRequest(new HttpRequestEvent([
            'requestContext' => ['http' => ['method' => 'POST']],
            'multiValueHeaders' => ['content-type' => ['application/json']],
            'body' => '{"foo":"bar"}',
        ]), $this->getContext());
        $this->assertSame('{"foo":"bar"}', $request->getContent());

        $request = SymfonyRequestBridge::convertRequest(new HttpRequestEvent([
            'requestContext' => ['http' => ['method' => 'POST']],
            'multiValueHeaders' => ['content-type' => ['application/x-www-form-urlencoded']],
            'body' => 'form%5Bname%5D=test&form%5Bsubmit%5D=',
        ]), $this->getContext());
        $this->assertSame('form%5Bname%5D=test&form%5Bsubmit%5D=', $request->getContent());

        // Multipart
        $request = SymfonyRequestBridge::convertRequest(new HttpRequestEvent([
            'requestContext' => ['http' => ['method' => 'POST']],
            'multiValueHeaders' => ['content-type' => ['multipart/form-data; boundary=----------------------------83ff53821b7c']],
            'body' => <<<HTTP
------------------------------83ff53821b7c
Content-Disposition: form-data; name="foo"

bar
------------------------------83ff53821b7c
Content-Disposition: form-data; name="rfc5987"; text1*=iso-8859-1'en'%A3%20rates; text2*=UTF-8''%c2%a3%20and%20%e2%82%ac%20rates

rfc
------------------------------83ff53821b7c--

HTTP
,
        ]), $this->getContext());
        $this->assertSame('', $request->getContent());
    }

    public function testUploadedFile()
    {
        $request = SymfonyRequestBridge::convertRequest(new HttpRequestEvent([
            'requestContext' => ['http' => ['method' => 'POST']],
            'multiValueHeaders' => ['content-type' => ['multipart/form-data; boundary=----------------------------83ff53821b7c']],
            'body' => <<<HTTP
------------------------------83ff53821b7c
Content-Disposition: form-data; name="form[img]"; filename="a.png"
Content-Type: image/png

?PNG

IHD?wS??iCCPICC Profilex?T?kA?6n??Zk?x?"IY?hE?6?bk
Y?<ߡ)??????9Nyx?+=?Y"|@5-?M?S?%?@?H8??qR>?׋??inf???O?????b??N?????~N??>?!?
??V?J?p?8?da?sZHO?Ln?}&???wVQ?y?g????E??0
 ??
   IDAc????????-IEND?B`?
------------------------------83ff53821b7c
Content-Disposition: form-data; name="foo"

bar
------------------------------83ff53821b7c--

HTTP
,
        ]), $this->getContext());
        $files = $request->files->all();
        $this->assertArrayHasKey('img', $files['form']);
        $this->assertInstanceOf(UploadedFile::class, $files['form']['img']);
        /** @var UploadedFile $file */
        $file = $files['form']['img'];
        $this->assertSame('a.png', $file->getClientOriginalName());
        $this->assertSame('image/png', $file->getClientMimeType());
        $this->assertSame(UPLOAD_ERR_OK, $file->getError());

        $post = $request->request->all();
        $this->assertArrayHasKey('foo', $post);
        $this->assertSame('bar', $post['foo']);
    }

    public function testEmptyUploadedFile()
    {
        $request = SymfonyRequestBridge::convertRequest(new HttpRequestEvent([
            'requestContext' => ['http' => ['method' => 'POST']],
            'multiValueHeaders' => ['content-type' => ['multipart/form-data; boundary=----------------------------83ff53821b7c']],
            'body' => <<<HTTP
------------------------------83ff53821b7c
Content-Disposition: form-data; name="form[img]"; filename=""
Content-Type: application/octet-stream


------------------------------83ff53821b7c
Content-Disposition: form-data; name="foo"

bar
------------------------------83ff53821b7c--

HTTP
,
        ]), $this->getContext());
        $files = $request->files->all();
        $this->assertArrayHasKey('img', $files['form']);
        $this->assertNull($files['form']['img']);

        $post = $request->request->all();
        $this->assertArrayHasKey('foo', $post);
        $this->assertSame('bar', $post['foo']);
    }

    public function testLambdaContext()
    {
        $requestContext = ['http' => ['method' => 'GET']];
        $request = SymfonyRequestBridge::convertRequest(new HttpRequestEvent([
            'requestContext' => $requestContext,
        ]), $invocationContext = $this->getContext());
        $this->assertTrue($request->server->has('LAMBDA_INVOCATION_CONTEXT'));
        $this->assertTrue($request->server->has('LAMBDA_REQUEST_CONTEXT'));

        $this->assertSame(json_encode($invocationContext), $request->server->get('LAMBDA_INVOCATION_CONTEXT'));
        $this->assertSame(json_encode($requestContext), $request->server->get('LAMBDA_REQUEST_CONTEXT'));
    }

    private function getContext()
    {
        return new Context('id', 1000, 'function', 'trace');
    }
}
