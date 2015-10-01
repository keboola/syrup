<?php
/**
 * Created by Ondrej Hlavacek <ondra@keboola.com>
 * Date: 01/10/2015
 */

namespace Keboola\Syrup\Tests\Service\Encryptor;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CryptoWrapperTest extends WebTestCase
{

    /**
     * @covers \Keboola\Syrup\Encryption\CryptoWrapper::encrypt
     * @covers \Keboola\Syrup\Encryption\CryptoWrapper::decrypt
     */
    public function testEncryptor()
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $encryptor = $container->get('syrup.crypto_wrapper');

        $encrypted = $encryptor->encrypt('secret');

        $this->assertEquals('secret', $encryptor->decrypt($encrypted));
    }
}
