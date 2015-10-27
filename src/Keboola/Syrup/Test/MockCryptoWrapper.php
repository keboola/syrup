<?php

namespace Keboola\Syrup\Test;

use Keboola\Syrup\Encryption\CryptoWrapperInterface;

class MockCryptoWrapper implements CryptoWrapperInterface
{
    /**
     * @inheritdoc
     */
    public function getPrefix()
    {
        return 'KBC::MockCryptoWrapper==';
    }

    /**
     * @inheritdoc
     */
    public function encrypt($data)
    {
        return $data;
    }


    /**
     * @inheritdoc
     */
    public function decrypt($encryptedData)
    {
        return $encryptedData;
    }
}
