<?php
/**
 * SyrupExceptionInterface.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 17.4.13
 */

namespace Keboola\Syrup\Exception;

interface SyrupExceptionInterface
{
    /**
     * @return array
     */
    public function getData();

    /**
     * @param array $data
     */
    public function setData(array $data);

    public function getStatusCode();
}
