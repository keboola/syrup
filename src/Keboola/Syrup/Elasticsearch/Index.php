<?php
/**
 * @package syrup
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Keboola\Syrup\Exception\ApplicationException;

class Index
{
    /**
     * @var Client
     */
    protected $client;
    protected $indexPrefix;
    protected $componentName;

    public function __construct($componentName, $indexPrefix, Client $client)
    {
        $this->client = $client;
        $this->indexPrefix = $indexPrefix;
        $this->componentName = $componentName;
    }

    /**
     * @param null $mapping
     * @return string Updated index name
     */
    public function putMapping($mapping = null)
    {
        $params['index'] = $this->getLastIndexName();
        $params['type'] = 'jobs';
        $params['body'] = $mapping;

        $this->client->indices()->putMapping($params);
        return $params['index'];
    }

    public function getIndexPrefix()
    {
        return $this->indexPrefix;
    }

    public function getIndexName()
    {
        return $this->indexPrefix . '_syrup_' . $this->componentName;
    }

    public function getIndexNameCurrent()
    {
        return $this->getIndexName() . '_current' ;
    }

    protected function getLastIndexName()
    {
        try {
            $indices = $this->client->indices()->getAlias([
                'name'  => $this->getIndexName()
            ]);

            return self::findLastIndexName(array_keys($indices));

        } catch (Missing404Exception $e) {
            return null;

        }
    }

    public function createIndex($settings = null, $mappings = null)
    {
        // Assemble new index name

        $nextIndexNumber = 1;
        $lastIndexName = $this->getLastIndexName();

        if (null != $lastIndexName) {
            $lastIndexNameArr = explode('_', $lastIndexName);
            $nextIndexNumber = array_pop($lastIndexNameArr) + 1;
        }

        $nextIndexName = $this->getIndexName() . '_' . date('Y') . '_' . $nextIndexNumber;

        // Create new index
        $params['index'] = $nextIndexName;
        if (null != $settings) {
            $params['body']['settings'] = $settings;
        }
        if (null != $mappings) {
            $params['body']['mappings'] = $mappings;
        }

        $this->client->indices()->create($params);

        // Update aliases
        $params = [];
        $params['body'] = [
            'actions' => [
                [
                    'add' => [
                        'index' => $nextIndexName,
                        'alias' => $this->getIndexNameCurrent()
                    ]
                ],
                [
                    'add' => [
                        'index' => $nextIndexName,
                        'alias' => $this->getIndexName()
                    ]
                ]
            ]
        ];

        if (null != $lastIndexName) {
            array_unshift($params['body']['actions'], [
                'remove' => [
                    'index' => $lastIndexName,
                    'alias' => $this->getIndexNameCurrent()
                ]
            ]);
        }

        $this->client->indices()->updateAliases($params);

        return $nextIndexName;
    }

    /**
     * Returns newest index name
     * Expected indices format: some_prefix_YYYY_VERSION where VERSION is number
     *
     * @param array $indexNames
     * @return mixed
     */
    public static function findLastIndexName(array $indexNames)
    {
        usort($indexNames, function($a, $b) {
            $aYear = self::getYearFromIndexName($a);
            $bYear = self::getYearFromIndexName($b);

            if ($aYear == $bYear) {
                $aVersion = self::getVersionFromIndexName($a);
                $bVersion = self::getVersionFromIndexName($b);

                if ($aVersion == $bVersion) {
                    return 0;
                }
                return ($aVersion < $bVersion) ? -1 : 1;
            }

            return ($aYear < $bYear) ? -1 : 1;
        });
        return array_pop($indexNames);
    }

    public static function getVersionFromIndexName($indexName)
    {
        self::validateIndexNameFormat($indexName);
        $parts = explode('_', $indexName);
        return (int) array_pop($parts);
    }

    public static function getYearFromIndexName($indexName)
    {
        self::validateIndexNameFormat($indexName);
        $parts = explode('_', $indexName);
        return (int) $parts[count($parts) - 2];
    }

    public static function validateIndexNameFormat($indexName)
    {
        $parts = explode('_', $indexName);
        if (count($parts) < 3) {
            throw new \Exception("Invalid index name: $indexName");
        }
    }

    public static function buildMainMapping($rootDir)
    {
        $mappingFile = realpath($rootDir.'/Resources/Elasticsearch/mapping_template.json');
        $mappingJson = file_get_contents($mappingFile);
        $mapping = json_decode($mappingJson, true);
        if ($mapping === null) {
            throw new ApplicationException('Error in Syrup template mapping, check '.$mappingFile);
        }
        return $mapping;
    }

    public static function buildCustomMapping($mappingFile)
    {
        $customMappingJson = file_get_contents($mappingFile);
        $customMapping = json_decode($customMappingJson, true);
        if ($customMapping === null) {
            throw new ApplicationException("Error in component's mapping, check ".realpath($mappingFile));
        }
        return $customMapping;
    }

    public static function buildMapping($rootDir)
    {
        $mapping = self::buildMainMapping($rootDir);

        if (file_exists($rootDir.'/../../../../Resources/Elasticsearch/mapping.json')) {
            $customMapping = self::buildCustomMapping($rootDir.'/../../../../Resources/Elasticsearch/mapping.json');
            $mapping['mappings']['jobs']['properties'] = array_merge($mapping['mappings']['jobs']['properties'], $customMapping);
        }

        return $mapping;
    }
}
