<?php
namespace Keboola\Syrup\Service\StorageApi;

use Keboola\StorageApi\Client;

/**
 * KBC project limits
 *
 * @author Erik Zigo <erik@keboola.com>
 */
class Limits
{
    /**
     * List of components with unlimited parallel job processing
     *
     * @return array
     */
    public static function unlimitedComponents()
    {
        return array(
            'orchestrator',
        );
    }

    /**
     * @param Client $storageApi
     * @return bool
     */
    public static function hasParallelLimit(Client $storageApi)
    {
        if (self::getParallelLimit($storageApi)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Parallel jobs limit of KBC project, null if unlimited
     *
     * @param Client $storageApi
     * @return int|null
     */
    public static function getParallelLimit(Client $storageApi)
    {
        $data = $storageApi->getLogData();
        if (!empty($data['owner']['features'])) {
            foreach ($data['owner']['features'] as $feature) {
                $matches = array();
                if (preg_match('/^syrup\-jobs\-limit\-([0-9]+)$/ui', $feature, $matches)) {
                    return (int) $matches[1];
                }
            }
        }

        return null;
    }
}
