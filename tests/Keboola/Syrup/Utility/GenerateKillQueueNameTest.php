<?php

namespace Keboola\Syrup\Tests\Service;

use Keboola\Syrup\Utility\Utility;

class GenerateKillQueueNameTest extends \PHPUnit_Framework_TestCase
{
    public function testReplaceDomainName()
    {
        $this->assertEquals(
            'syrup_kill_i_0964292c20ee25e51',
            Utility::generateKillQueueName('i-0964292c20ee25e51.keboola.com')
        );
    }

    public function testReplaceDotByUnderscore()
    {
        $this->assertEquals(
            'syrup_kill_ip_10_0_41_63_ec2_internal',
            Utility::generateKillQueueName('ip-10-0-41-63.ec2.internal')
        );
    }
}
