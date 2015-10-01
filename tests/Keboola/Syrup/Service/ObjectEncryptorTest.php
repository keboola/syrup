<?php
/**
 * Created by Ondrej Hlavacek <ondra@keboola.com>
 * Date: 01/10/2015
 */

namespace Keboola\Syrup\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ObjectEncryptorTest extends WebTestCase
{

    // TODO failed decryption test

    /**
     * @covers \Keboola\Syrup\Service\ObjectEncryptor::encrypt
     * @covers \Keboola\Syrup\Service\ObjectEncryptor::decrypt
     */
    public function testEncryptorScalar()
    {
        $client = static::createClient();
        $encryptor = $client->getContainer()->get('syrup.object_encryptor');

        $originalText = 'secret';
        $encrypted = $encryptor->encrypt($originalText);
        $this->assertEquals("KBC::Encrypted==", substr($encrypted, 0, 16));
        $this->assertEquals($originalText, $encryptor->decrypt($encrypted));
    }

    //
    /**
     * @expectedException \Keboola\Syrup\Exception\UserException
     * @expectedExceptionMessage 'test' is not an encrypted value.
     */
    public function testDecryptorScalarException()
    {
        $client = static::createClient();
        $encryptor = $client->getContainer()->get('syrup.object_encryptor');
        $encryptor->decrypt('test');
    }

    /**
     * @covers \Keboola\Syrup\Service\ObjectEncryptor::encrypt
     * @covers \Keboola\Syrup\Service\ObjectEncryptor::decrypt
     */
    public function testEncryptorScalarAlreadyEncrypted()
    {
        $client = static::createClient();
        $encryptor = $client->getContainer()->get('syrup.object_encryptor');

        $encryptedValue = $encryptor->encrypt("test");

        $encrypted = $encryptor->encrypt($encryptedValue);
        $this->assertEquals("KBC::Encrypted==", substr($encrypted, 0, 16));
        $this->assertEquals("test", $encryptor->decrypt($encrypted));
    }

    /**
     * @covers \Keboola\Syrup\Service\ObjectEncryptor::encrypt
     * @covers \Keboola\Syrup\Service\ObjectEncryptor::decrypt
     */
    public function testEncryptorSimpleObject()
    {
        $client = static::createClient();
        $encryptor = $client->getContainer()->get('syrup.object_encryptor');

        $object = [
            "key1" => "value1",
            "#key2" => "value2"
        ];
        $result = $encryptor->encrypt($object);
        $this->assertArrayHasKey("key1", $result);
        $this->assertArrayHasKey("#key2", $result);
        $this->assertEquals("value1", $result["key1"]);
        $this->assertEquals("KBC::Encrypted==", substr($result["#key2"], 0, 16));

        $decrypted = $encryptor->decrypt($result);
        $this->assertArrayHasKey("key1", $decrypted);
        $this->assertArrayHasKey("key2", $decrypted);
        $this->assertEquals("value1", $decrypted["key1"]);
        $this->assertEquals("value2", $decrypted["key2"]);
    }

    /**
     * @covers \Keboola\Syrup\Service\ObjectEncryptor::encrypt
     * @covers \Keboola\Syrup\Service\ObjectEncryptor::decrypt
     */
    public function testEncryptorSimpleObjectEncrypted()
    {
        $client = static::createClient();
        $encryptor = $client->getContainer()->get('syrup.object_encryptor');

        $encryptedValue = $encryptor->encrypt("test");
        $object = [
            "key1" => "value1",
            "#key2" => $encryptedValue
        ];
        $result = $encryptor->encrypt($object);
        $this->assertArrayHasKey("key1", $result);
        $this->assertArrayHasKey("#key2", $result);
        $this->assertEquals("value1", $result["key1"]);
        $this->assertEquals($encryptedValue, $result["#key2"]);

        $decrypted = $encryptor->decrypt($result);
        $this->assertArrayHasKey("key1", $decrypted);
        $this->assertArrayHasKey("key2", $decrypted);
        $this->assertEquals("value1", $decrypted["key1"]);
        $this->assertEquals("test", $decrypted["key2"]);
    }

    /**
     * @covers \Keboola\Syrup\Service\ObjectEncryptor::encrypt
     * @covers \Keboola\Syrup\Service\ObjectEncryptor::decrypt
     */
    public function testEncryptorNestedObject()
    {
        $client = static::createClient();
        $encryptor = $client->getContainer()->get('syrup.object_encryptor');


        $object = [
            "key1" => "value1",
            "key2" => [
                "nestedKey1" => "value2",
                "nestedKey2" => [
                    "#finalKey" => "value3"
                ]
            ]
        ];
        $result = $encryptor->encrypt($object);
        $this->assertArrayHasKey("key1", $result);
        $this->assertArrayHasKey("key2", $result);
        $this->assertArrayHasKey("nestedKey1", $result["key2"]);
        $this->assertArrayHasKey("nestedKey2", $result["key2"]);
        $this->assertArrayHasKey("#finalKey", $result["key2"]["nestedKey2"]);
        $this->assertEquals("value1", $result["key1"]);
        $this->assertEquals("value2", $result["key2"]["nestedKey1"]);
        $this->assertEquals("KBC::Encrypted==", substr($result["key2"]["nestedKey2"]["#finalKey"], 0, 16));

        $decrypted = $encryptor->decrypt($result);
        $this->assertArrayHasKey("key1", $decrypted);
        $this->assertArrayHasKey("key2", $decrypted);
        $this->assertArrayHasKey("nestedKey1", $decrypted["key2"]);
        $this->assertArrayHasKey("nestedKey2", $decrypted["key2"]);
        $this->assertArrayHasKey("finalKey", $decrypted["key2"]["nestedKey2"]);
        $this->assertEquals("value1", $decrypted["key1"]);
        $this->assertEquals("value2", $decrypted["key2"]["nestedKey1"]);
        $this->assertEquals("value3", $decrypted["key2"]["nestedKey2"]["finalKey"]);
    }

    /**
     * @covers \Keboola\Syrup\Service\ObjectEncryptor::encrypt
     * @covers \Keboola\Syrup\Service\ObjectEncryptor::decrypt
     */
    public function testEncryptorNestedObjectWithArrayKeyHashmark()
    {
        $client = static::createClient();
        $encryptor = $client->getContainer()->get('syrup.object_encryptor');

        $object = [
            "key1" => "value1",
            "key2" => [
                "nestedKey1" => "value2",
                "nestedKey2" => [
                    "#finalKey" => "value3"
                ]
            ],
            "#key3" => [
                "anotherNestedKey" => "someValue"
            ]
        ];
        $result = $encryptor->encrypt($object);
        $this->assertArrayHasKey("key1", $result);
        $this->assertArrayHasKey("key2", $result);
        $this->assertArrayHasKey("#key3", $result);
        $this->assertArrayHasKey("nestedKey1", $result["key2"]);
        $this->assertArrayHasKey("nestedKey2", $result["key2"]);
        $this->assertArrayHasKey("#finalKey", $result["key2"]["nestedKey2"]);
        $this->assertArrayHasKey("anotherNestedKey", $result["#key3"]);
        $this->assertEquals("value1", $result["key1"]);
        $this->assertEquals("value2", $result["key2"]["nestedKey1"]);
        $this->assertEquals("someValue", $result["#key3"]["anotherNestedKey"]);
        $this->assertEquals("KBC::Encrypted==", substr($result["key2"]["nestedKey2"]["#finalKey"], 0, 16));

        $decrypted = $encryptor->decrypt($result);
        $this->assertArrayHasKey("key1", $decrypted);
        $this->assertArrayHasKey("key2", $decrypted);
        $this->assertArrayHasKey("#key3", $decrypted);
        $this->assertArrayHasKey("nestedKey1", $decrypted["key2"]);
        $this->assertArrayHasKey("nestedKey2", $decrypted["key2"]);
        $this->assertArrayHasKey("finalKey", $decrypted["key2"]["nestedKey2"]);
        $this->assertEquals("value1", $decrypted["key1"]);
        $this->assertEquals("value2", $decrypted["key2"]["nestedKey1"]);
        $this->assertEquals("value3", $decrypted["key2"]["nestedKey2"]["finalKey"]);
        $this->assertEquals("someValue", $decrypted["#key3"]["anotherNestedKey"]);
    }

    /**
     * @covers \Keboola\Syrup\Service\ObjectEncryptor::encrypt
     * @covers \Keboola\Syrup\Service\ObjectEncryptor::decrypt
     */
    public function testEncryptorNestedObjectEncrypted()
    {
        $client = static::createClient();
        $encryptor = $client->getContainer()->get('syrup.object_encryptor');

        $encryptedValue = $encryptor->encrypt("test");
        $object = [
            "key1" => "value1",
            "key2" => [
                "nestedKey1" => "value2",
                "nestedKey2" => [
                    "#finalKey" => "value3",
                    "#finalKeyEncrypted" => $encryptedValue
                ]
            ]
        ];
        $result = $encryptor->encrypt($object);
        $this->assertArrayHasKey("key1", $result);
        $this->assertArrayHasKey("key2", $result);
        $this->assertArrayHasKey("nestedKey1", $result["key2"]);
        $this->assertArrayHasKey("nestedKey2", $result["key2"]);
        $this->assertArrayHasKey("#finalKey", $result["key2"]["nestedKey2"]);
        $this->assertArrayHasKey("#finalKeyEncrypted", $result["key2"]["nestedKey2"]);
        $this->assertEquals("value1", $result["key1"]);
        $this->assertEquals("value2", $result["key2"]["nestedKey1"]);
        $this->assertEquals("KBC::Encrypted==", substr($result["key2"]["nestedKey2"]["#finalKey"], 0, 16));
        $this->assertEquals($encryptedValue, $result["key2"]["nestedKey2"]["#finalKeyEncrypted"]);

        $decrypted = $encryptor->decrypt($result);
        $this->assertArrayHasKey("key1", $decrypted);
        $this->assertArrayHasKey("key2", $decrypted);
        $this->assertArrayHasKey("nestedKey1", $decrypted["key2"]);
        $this->assertArrayHasKey("nestedKey2", $decrypted["key2"]);
        $this->assertArrayHasKey("finalKey", $decrypted["key2"]["nestedKey2"]);
        $this->assertEquals("value1", $decrypted["key1"]);
        $this->assertEquals("value2", $decrypted["key2"]["nestedKey1"]);
        $this->assertEquals("value3", $decrypted["key2"]["nestedKey2"]["finalKey"]);
        $this->assertEquals("test", $decrypted["key2"]["nestedKey2"]["finalKeyEncrypted"]);
    }
}
