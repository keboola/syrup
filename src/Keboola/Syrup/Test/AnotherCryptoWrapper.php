<?php

namespace Keboola\Syrup\Test;

use Doctrine\ORM\Query\Expr\Base;
use Keboola\Syrup\Encryption\BaseWrapper;
use Keboola\Syrup\Encryption\CryptoWrapperInterface;

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
