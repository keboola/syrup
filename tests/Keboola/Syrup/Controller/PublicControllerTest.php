<?php

namespace Keboola\Syrup\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Keboola\Syrup\Controller\ApiController;
use Keboola\Syrup\Test\WebTestCase;

class PublicControllerTest extends WebTestCase
{
    /** @var Client */
    private static $client;

    /** @var ApiController */
    protected $controller;

    /** @var ContainerInterface */
    protected $container;

    public function setUp()
    {
        self::$client = static::createClient();
        $container = static::$client->getContainer();

        $request = Request::create('/syrup/run', 'POST');
        $container->get('request_stack')->push($request);

        $this->controller = new ApiController();
        $this->controller->setContainer($container);

        $this->container = $container;
    }

    public function testEncryptActionSimpleText()
    {
        static::$client->request('POST', '/syrup/encrypt', [], [], ['CONTENT_TYPE' => 'text/plain'], 'abcd');
        $result = static::$client->getResponse()->getContent();
        $this->assertEquals("KBC::Encrypted==", substr($result, 0, 16));
    }

    public function testEncryptActionSimpleTextContentType()
    {
        static::$client->request(
            'POST',
            '/syrup/encrypt',
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/plain;charset=UTF-8'
            ],
            'abcd'
        );

        $result = static::$client->getResponse()->getContent();
        $this->assertEquals("KBC::Encrypted==", substr($result, 0, 16));
    }

    public function testEncryptActionSimpleTextAlreadyEncrypted()
    {
        static::$client->request(
            'POST',
            '/syrup/encrypt',
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/plain'
            ],
            'KBC::Encrypted==abcd'
        );

        $result = static::$client->getResponse()->getContent();
        $this->assertEquals("KBC::Encrypted==abcd", $result);
    }

    public function testEncryptActionSimpleJson()
    {
        static::$client->request(
            'POST',
            '/syrup/encrypt',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json'
            ],
            '{"key1": "value1", "#key2": "value2"}'
        );

        $result = json_decode(static::$client->getResponse()->getContent(), true);
        $this->assertArrayHasKey("key1", $result);
        $this->assertArrayHasKey("#key2", $result);
        $this->assertEquals("value1", $result["key1"]);
        $this->assertEquals("KBC::Encrypted==", substr($result["#key2"], 0, 16));
    }

    public function testEncryptActionSimpleJsonContentType()
    {
        static::$client->request(
            'POST',
            '/syrup/encrypt',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json; Charset=utf-8'
            ],
            '{"key1": "value1", "#key2": "value2"}'
        );

        $result = json_decode(static::$client->getResponse()->getContent(), true);
        $this->assertArrayHasKey("key1", $result);
        $this->assertArrayHasKey("#key2", $result);
        $this->assertEquals("value1", $result["key1"]);
        $this->assertEquals("KBC::Encrypted==", substr($result["#key2"], 0, 16));
    }



    public function testEncryptActionSimpleJsonEncrypted()
    {
        static::$client->request(
            'POST',
            '/syrup/encrypt',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json'
            ],
            '{"key1": "value1", "#key2": "KBC::Encrypted==abcd", "#key3": "value3"}'
        );

        $result = json_decode(static::$client->getResponse()->getContent(), true);
        $this->assertArrayHasKey("key1", $result);
        $this->assertArrayHasKey("#key2", $result);
        $this->assertArrayHasKey("#key3", $result);
        $this->assertEquals("value1", $result["key1"]);
        $this->assertEquals("KBC::Encrypted==abcd", $result["#key2"]);
        $this->assertEquals("KBC::Encrypted==", substr($result["#key3"], 0, 16));
    }

    public function testEncryptActionNestedJson()
    {
        static::$client->request(
            'POST',
            '/syrup/encrypt',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json'
            ],
            '{"key1": "value1", "key2": {"nestedKey1": "value2", "nestedKey2": {"#finalKey": "value3"}}}'
        );

        $result = json_decode(static::$client->getResponse()->getContent(), true);
        $this->assertArrayHasKey("key1", $result);
        $this->assertArrayHasKey("key2", $result);
        $this->assertArrayHasKey("nestedKey1", $result["key2"]);
        $this->assertArrayHasKey("nestedKey2", $result["key2"]);
        $this->assertArrayHasKey("#finalKey", $result["key2"]["nestedKey2"]);
        $this->assertEquals("value1", $result["key1"]);
        $this->assertEquals("value2", $result["key2"]["nestedKey1"]);
        $this->assertEquals("KBC::Encrypted==", substr($result["key2"]["nestedKey2"]["#finalKey"], 0, 16));
    }

    public function testEncryptActionNestedJsonAlreadyEncrypted()
    {
        static::$client->request(
            'POST',
            '/syrup/encrypt',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json'
            ],
            '{"key1": "value1", "key2": {"nestedKey1": "value2", "nestedKey2": {"#finalKey": "value3", "#finalKeyEncrypted": "KBC::Encrypted==abcd"}}}'
        );

        $result = json_decode(static::$client->getResponse()->getContent(), true);
        $this->assertArrayHasKey("key1", $result);
        $this->assertArrayHasKey("key2", $result);
        $this->assertArrayHasKey("nestedKey1", $result["key2"]);
        $this->assertArrayHasKey("nestedKey2", $result["key2"]);
        $this->assertArrayHasKey("#finalKey", $result["key2"]["nestedKey2"]);
        $this->assertEquals("value1", $result["key1"]);
        $this->assertEquals("value2", $result["key2"]["nestedKey1"]);
        $this->assertEquals("KBC::Encrypted==", substr($result["key2"]["nestedKey2"]["#finalKey"], 0, 16));
        $this->assertEquals("KBC::Encrypted==abcd", $result["key2"]["nestedKey2"]["#finalKeyEncrypted"]);
    }


    public function testEncryptActionEmtpyArrayEmptyObject()
    {
        static::$client->request(
            'POST',
            '/syrup/encrypt',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json'
            ],
            '{"key1": "value1", "key2": {}, "key3": []}'
        );

        $result = json_decode(static::$client->getResponse()->getContent());
        $this->assertObjectHasAttribute("key1", $result);
        $this->assertObjectHasAttribute("key2", $result);
        $this->assertObjectHasAttribute("key3", $result);
        $this->assertEquals("value1", $result->key1);
        $this->assertEquals("stdClass", get_class($result->key2));
        $this->assertTrue(is_array($result->key3));
    }

    public function testEncryptActionIncorrectContentType()
    {
        static::$client->request(
            'POST',
            '/syrup/encrypt',
            [],
            [],
            [
                'CONTENT_TYPE' => 'aa/bb'
            ],
            'abcd'
        );

        $result = json_decode(static::$client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('error', $result['status']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('User error', $result['error']);
        $this->assertArrayHasKey('code', $result);
        $this->assertEquals(400, $result['code']);
        $this->assertArrayHasKey('exceptionId', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('runId', $result);
        $this->assertArrayNotHasKey('context', $result);
        $this->assertEquals('Incorrect Content-Type.', $result["message"]);
    }
}
