<?php
/**
 * Created by Ondrej Hlavacek <ondra@keboola.com>
 * Date: 01/10/2015
 */

namespace Keboola\Syrup\Tests\Service;

use Keboola\Syrup\Exception\ApplicationException;
use Keboola\Syrup\Exception\UserException;
use Keboola\Syrup\Service\ObjectEncryptor;
use Keboola\Syrup\Test\AnotherCryptoWrapper;
use Keboola\Syrup\Test\MockCryptoWrapper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ObjectEncryptorTest extends WebTestCase
{

    public function testEncryptorScalar()
    {
        $client = static::createClient();
        $encryptor = $client->getContainer()->get('syrup.object_encryptor');

        $originalText = 'secret';
        $encrypted = $encryptor->encrypt($originalText);
        $this->assertEquals("KBC::Encrypted==", substr($encrypted, 0, 16));
        $this->assertEquals($originalText, $encryptor->decrypt($encrypted));
    }

    public function testEncryptorInvalidService()
    {
        $client = static::createClient();
        $encryptor = $client->getContainer()->get('syrup.object_encryptor');
        try {
            $encryptor->encrypt('secret', 'fooBar');
            $this->fail("Invalid crypto wrapper must throw exception");
        } catch (ApplicationException $e) {
        }
    }

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

    public function testEncryptorUnsupportedInput()
    {
        $client = static::createClient();
        $encryptor = $client->getContainer()->get('syrup.object_encryptor');

        $unsupportedInput = new \stdClass();
        try {
            $encryptor->encrypt($unsupportedInput);
            $this->fail("Encryption of invalid data should fail.");
        } catch (ApplicationException $e) {
        }

        $unsupportedInput = [
            'key' => 'value',
            'key2' => new \stdClass(),
        ];
        try {
            $encryptor->encrypt($unsupportedInput);
            $this->fail("Encryption of invalid data should fail.");
        } catch (ApplicationException $e) {
        }

        $unsupportedInput = [
            'key' => 'value',
            '#key2' => new \stdClass(),
        ];
        try {
            $encryptor->encrypt($unsupportedInput);
            $this->fail("Encryption of invalid data should fail.");
        } catch (ApplicationException $e) {
        }
    }

    public function testDecryptorUnsupportedInput()
    {
        $client = static::createClient();
        $encryptor = $client->getContainer()->get('syrup.object_encryptor');

        $unsupportedInput = new \stdClass();
        try {
            $encryptor->decrypt($unsupportedInput);
            $this->fail("Encryption of invalid data should fail.");
        } catch (ApplicationException $e) {
        }

        $unsupportedInput = [
            'key' => 'value',
            'key2' => new \stdClass(),
        ];
        try {
            $encryptor->decrypt($unsupportedInput);
            $this->fail("Encryption of invalid data should fail.");
        } catch (ApplicationException $e) {
        }

        $unsupportedInput = [
            'key' => 'value',
            '#key2' => new \stdClass(),
        ];
        try {
            $encryptor->decrypt($unsupportedInput);
            $this->fail("Encryption of invalid data should fail.");
        } catch (ApplicationException $e) {
        }
    }

    public function testDecryptorInvalidCipherText()
    {
        $client = static::createClient();
        $encryptor = $client->getContainer()->get('syrup.object_encryptor');

        $encrypted = 'KBC::Encrypted==yI0sawothis is not a valid cipher lnRnr1muMztO0AMYmsjwbcJSA7zAOSpLFjUJN2Jg==';
        try {
            $this->assertEquals($encrypted, $encryptor->decrypt($encrypted));
            $this->fail("Invalid cipher text must raise exception");
        } catch (UserException $e) {
            $this->assertNotContains('KBC::Encrypted==yI0sawothis', $e->getMessage());
        }
    }

    public function testDecryptorInvalidCipherStructure()
    {
        $client = static::createClient();
        $encryptor = $client->getContainer()->get('syrup.object_encryptor');

        $encrypted = [
            'key1' => 'somevalue',
            'key2' => [
                '#anotherKey' => 'KBC::Encrypted==yI0sawothis is not a valid cipher lnRnr1muMmsjwbcJSA7zAOSpLFjUJN2Jg=='
            ]
        ];
        try {
            $this->assertEquals($encrypted, $encryptor->decrypt($encrypted));
            $this->fail("Invalid cipher text must raise exception");
        } catch (UserException $e) {
            $this->assertNotContains('KBC::Encrypted==yI0sawothis', $e->getMessage());
            $this->assertContains('#anotherKey', $e->getMessage());
        }
    }


    public function testEncryptorAlreadyEncrypted()
    {
        $client = static::createClient();
        $encryptor = $client->getContainer()->get('syrup.object_encryptor');

        $encryptedValue = $encryptor->encrypt("test");

        $encrypted = $encryptor->encrypt($encryptedValue);
        $this->assertEquals("KBC::Encrypted==", substr($encrypted, 0, 16));
        $this->assertEquals("test", $encryptor->decrypt($encrypted));
    }

    public function testEncryptorAlreadyEncryptedWrapper()
    {
        $client = static::createClient();

        /** @var ObjectEncryptor $encryptor */
        $encryptor = $client->getContainer()->get('syrup.object_encryptor');
        $wrapper = new MockCryptoWrapper();
        $client->getContainer()->set('mock.crypto.wrapper', $wrapper);
        $encryptor->pushWrapper($wrapper);

        $secret = 'secret';
        $encryptedValue = $encryptor->encrypt($secret, 'mock.crypto.wrapper');
        $this->assertEquals("KBC::MockCryptoWrapper==" . $secret, $encryptedValue);

        $encryptedSecond = $encryptor->encrypt($encryptedValue);
        $this->assertEquals("KBC::Encrypted==", substr($encryptedSecond, 0, 16));
        $this->assertEquals($encryptedValue, $encryptor->decrypt($encryptedSecond));
    }

    public function testInvalidWrapper()
    {
        $client = static::createClient();

        /** @var ObjectEncryptor $encryptor */
        $encryptor = $client->getContainer()->get('syrup.object_encryptor');
        $wrapper = new MockCryptoWrapper();
        $client->getContainer()->set('mock.crypto.wrapper', $wrapper);
        $encryptor->pushWrapper($wrapper);
        try {
            $encryptor->pushWrapper($wrapper);
            $this->fail("Adding crypto wrapper with same prefix must fail.");
        } catch (ApplicationException $e) {
        }
    }

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
        $this->assertArrayHasKey("#key2", $decrypted);
        $this->assertEquals("value1", $decrypted["key1"]);
        $this->assertEquals("value2", $decrypted["#key2"]);
    }

    public function testEncryptorSimpleObjectScalars()
    {
        $client = static::createClient();
        $encryptor = $client->getContainer()->get('syrup.object_encryptor');

        $object = [
            "key1" => "value1",
            "#key2" => "value2",
            "#key3" => true,
            "#key4" => 1,
            "#key5" => 1.5,
            "#key6" => null,
            "key7" => null
        ];
        $result = $encryptor->encrypt($object);
        $this->assertArrayHasKey("key1", $result);
        $this->assertArrayHasKey("#key2", $result);
        $this->assertEquals("value1", $result["key1"]);
        $this->assertEquals("KBC::Encrypted==", substr($result["#key2"], 0, 16));
        $this->assertEquals("KBC::Encrypted==", substr($result["#key3"], 0, 16));
        $this->assertEquals("KBC::Encrypted==", substr($result["#key4"], 0, 16));
        $this->assertEquals("KBC::Encrypted==", substr($result["#key5"], 0, 16));
        $this->assertEquals("KBC::Encrypted==", substr($result["#key6"], 0, 16));

        $decrypted = $encryptor->decrypt($result);
        $this->assertArrayHasKey("key1", $decrypted);
        $this->assertArrayHasKey("#key2", $decrypted);
        $this->assertEquals("value1", $decrypted["key1"]);
        $this->assertEquals("value2", $decrypted["#key2"]);
        $this->assertEquals(true, $decrypted["#key3"]);
        $this->assertEquals(1, $decrypted["#key4"]);
        $this->assertEquals(1.5, $decrypted["#key5"]);
        $this->assertEquals(null, $decrypted["#key6"]);
        $this->assertEquals(null, $decrypted["key7"]);
    }

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
        $this->assertArrayHasKey("#key2", $decrypted);
        $this->assertEquals("value1", $decrypted["key1"]);
        $this->assertEquals("test", $decrypted["#key2"]);
    }

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
        $this->assertArrayHasKey("#finalKey", $decrypted["key2"]["nestedKey2"]);
        $this->assertEquals("value1", $decrypted["key1"]);
        $this->assertEquals("value2", $decrypted["key2"]["nestedKey1"]);
        $this->assertEquals("value3", $decrypted["key2"]["nestedKey2"]["#finalKey"]);
    }

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
                "anotherNestedKey" => "someValue",
                "#encryptedNestedKey" => "someValue2"
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
        $this->assertEquals("KBC::Encrypted==", substr($result["#key3"]["#encryptedNestedKey"], 0, 16));
        $this->assertEquals("KBC::Encrypted==", substr($result["key2"]["nestedKey2"]["#finalKey"], 0, 16));

        $decrypted = $encryptor->decrypt($result);
        $this->assertArrayHasKey("key1", $decrypted);
        $this->assertArrayHasKey("key2", $decrypted);
        $this->assertArrayHasKey("#key3", $decrypted);
        $this->assertArrayHasKey("nestedKey1", $decrypted["key2"]);
        $this->assertArrayHasKey("nestedKey2", $decrypted["key2"]);
        $this->assertArrayHasKey("#finalKey", $decrypted["key2"]["nestedKey2"]);
        $this->assertEquals("value1", $decrypted["key1"]);
        $this->assertEquals("value2", $decrypted["key2"]["nestedKey1"]);
        $this->assertEquals("value3", $decrypted["key2"]["nestedKey2"]["#finalKey"]);
        $this->assertEquals("someValue", $decrypted["#key3"]["anotherNestedKey"]);
        $this->assertEquals("someValue2", $decrypted["#key3"]["#encryptedNestedKey"]);
    }

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
        $this->assertArrayHasKey("#finalKey", $decrypted["key2"]["nestedKey2"]);
        $this->assertArrayHasKey("#finalKeyEncrypted", $decrypted["key2"]["nestedKey2"]);
        $this->assertEquals("value1", $decrypted["key1"]);
        $this->assertEquals("value2", $decrypted["key2"]["nestedKey1"]);
        $this->assertEquals("value3", $decrypted["key2"]["nestedKey2"]["#finalKey"]);
        $this->assertEquals("test", $decrypted["key2"]["nestedKey2"]["#finalKeyEncrypted"]);
    }

    /**
     * @covers \Keboola\Syrup\Service\ObjectEncryptor::encrypt
     * @covers \Keboola\Syrup\Service\ObjectEncryptor::decrypt
     */
    public function testEncryptorNestedObjectWithArray()
    {
        $client = static::createClient();
        $encryptor = $client->getContainer()->get('syrup.object_encryptor');


        $object = [
            "key1" => "value1",
            "key2" => [
                ["nestedKey1" => "value2"],
                ["nestedKey2" => ["#finalKey" => "value3"]]
            ]
        ];
        $result = $encryptor->encrypt($object);
        $this->assertArrayHasKey("key1", $result);
        $this->assertArrayHasKey("key2", $result);
        $this->assertCount(2, $result["key2"]);
        $this->assertArrayHasKey("nestedKey1", $result["key2"][0]);
        $this->assertArrayHasKey("nestedKey2", $result["key2"][1]);
        $this->assertArrayHasKey("#finalKey", $result["key2"][1]["nestedKey2"]);
        $this->assertEquals("value1", $result["key1"]);
        $this->assertEquals("value2", $result["key2"][0]["nestedKey1"]);
        $this->assertEquals("KBC::Encrypted==", substr($result["key2"][1]["nestedKey2"]["#finalKey"], 0, 16));

        $decrypted = $encryptor->decrypt($result);
        $this->assertArrayHasKey("key1", $decrypted);
        $this->assertArrayHasKey("key2", $decrypted);
        $this->assertCount(2, $result["key2"]);
        $this->assertArrayHasKey("nestedKey1", $decrypted["key2"][0]);
        $this->assertArrayHasKey("nestedKey2", $decrypted["key2"][1]);
        $this->assertArrayHasKey("#finalKey", $decrypted["key2"][1]["nestedKey2"]);
        $this->assertEquals("value1", $decrypted["key1"]);
        $this->assertEquals("value2", $decrypted["key2"][0]["nestedKey1"]);
        $this->assertEquals("value3", $decrypted["key2"][1]["nestedKey2"]["#finalKey"]);
    }

    public function testMixedCryptoWrappersDecrypt()
    {
        $client = static::createClient();
        /**
         * @var $encryptor ObjectEncryptor
         */
        $encryptor = $client->getContainer()->get('syrup.object_encryptor');
        $wrapper = new AnotherCryptoWrapper(md5(uniqid()));
        $client->getContainer()->set('another.crypto.wrapper', $wrapper);
        $encryptor->pushWrapper($wrapper);

        $object = [
            "#key1" => $encryptor->encrypt("value1"),
            "#key2" => $encryptor->encrypt("value2", 'another.crypto.wrapper')
        ];
        $this->assertEquals("KBC::Encrypted==", substr($object["#key1"], 0, 16));
        $this->assertEquals("KBC::AnotherCryptoWrapper==", substr($object["#key2"], 0, 27));

        $decrypted = $encryptor->decrypt($object);
        $this->assertArrayHasKey("#key1", $decrypted);
        $this->assertArrayHasKey("#key2", $decrypted);
        $this->assertCount(2, $decrypted);
        $this->assertEquals("value1", $decrypted["#key1"]);
        $this->assertEquals("value2", $decrypted["#key2"]);
    }

    public function testEncryptorNoWrappers()
    {
        $client = static::createClient();
        $encryptor = new ObjectEncryptor($client->getContainer());
        try {
            $encryptor->encrypt("test");
            $this->fail("Misconfigured object encryptor must raise exception.");
        } catch (ApplicationException $e) {
        }
    }
}
