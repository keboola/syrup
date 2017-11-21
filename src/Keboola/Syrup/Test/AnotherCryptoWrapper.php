<?php

namespace Keboola\Syrup\Test;

use Keboola\ObjectEncryptor\Legacy\Wrapper\BaseWrapper;

class AnotherCryptoWrapper extends BaseWrapper
{
    /**
     * @inheritdoc
     */
    public function getPrefix()
    {
        return 'KBC::AnotherCryptoWrapper==';
    }
}
