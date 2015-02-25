<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 19/02/14
 * Time: 17:27
 */

namespace Keboola\Syrup\Tests\Service\Encryptor;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EncryptorTest extends WebTestCase
{

    /**
     * @covers \Keboola\Syrup\Encryption\Encryptor::encrypt
     * @covers \Keboola\Syrup\Encryption\Encryptor::decrypt
     */
    public function testEncryptor()
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $encryptor = $container->get('syrup.encryptor');

        $encrypted = $encryptor->encrypt('secret');

        $this->assertEquals('secret', $encryptor->decrypt($encrypted));
    }
}
