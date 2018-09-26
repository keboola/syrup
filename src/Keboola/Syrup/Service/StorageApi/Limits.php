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
        $data = $tokenData;
        if (!empty($data['owner']['limits'])) {
            foreach ($data['owner']['limits'] as $name => $limit) {
                if ($limit['name'] === self::PARALLEL_LIMIT_NAME && !empty($limit['value'])) {
                    return (int) $limit['value'];
                }
            }
        }

        return null;
    }
}
