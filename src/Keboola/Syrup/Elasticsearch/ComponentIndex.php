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

class ComponentIndex
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

    /**
     * Return mapping from latest index
     * @return array mapping resource
     */
    public function getMapping()
    {
        $params['index'] = $this->getLastIndexName();
        $params['type'] = 'jobs';

        return $this->client->indices()->getMapping($params);
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

            return IndexNameResolver::getLastIndexName(array_keys($indices));

        } catch (Missing404Exception $e) {
            return null;

        }
    }

    public function getIndices()
    {
        return array_keys($this->client->indices()->getAlias([
            'name'  => $this->getIndexName()
        ]));
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

    public function hasProperty($property)
    {
        $mapping = $this->getMapping();
        $mappings = array_shift($mapping);

        return isset($mappings['mappings']['jobs']['properties'][$property]);
    }
}
