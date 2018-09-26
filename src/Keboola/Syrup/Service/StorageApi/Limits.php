<?php
namespace Keboola\Syrup\Service\StorageApi;

/**
 * KBC project limits
 *
 * @author Erik Zigo <erik@keboola.com>
 */
class Limits
{
    const PARALLEL_LIMIT_NAME = 'components.jobsParallelism';

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
        if (!empty($tokenData['owner']['limits'][self::PARALLEL_LIMIT_NAME]['value'])) {
            return (int) $tokenData['owner']['limits'][self::PARALLEL_LIMIT_NAME]['value'];
        }

        return null;
    }
}
