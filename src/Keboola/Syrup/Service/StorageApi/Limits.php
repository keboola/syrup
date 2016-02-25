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
            'syrup',
            'orchestrator',
        );
    }

    /**
     * @param $tokenData
     * @return bool
     */
    public static function hasParallelLimit($tokenData)
    {
        if (self::getParallelLimit($tokenData)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Parallel jobs limit of KBC project, null if unlimited
     *
     * @param $tokenData
     * @return int|null
     */
    public static function getParallelLimit($tokenData)
    {
        $data = $tokenData;
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
