<?php
/**
 * @package syrup
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Elasticsearch;

use Keboola\Syrup\Exception\ApplicationException;

class Elasticsearch
{

    public static function getMainMapping($rootDir)
    {
        $mappingFile = realpath($rootDir.'/Resources/Elasticsearch/mapping_template.json');
        $mappingJson = file_get_contents($mappingFile);
        $mapping = json_decode($mappingJson, true);
        if ($mapping === null) {
            throw new ApplicationException('Error in Syrup template mapping, check '.$mappingFile);
        }
        return $mapping;
    }

    public static function getCustomMapping($mappingFile)
    {
        $customMappingJson = file_get_contents($mappingFile);
        $customMapping = json_decode($customMappingJson, true);
        if ($customMapping === null) {
            throw new ApplicationException("Error in component's mapping, check ".realpath($mappingFile));
        }
        return $customMapping;
    }

    /**
     * @deprecated
     */
    public static function getCustomMappingDeprecated($mappingFile)
    {
        $customMappingJson = file_get_contents($mappingFile);
        $startTagPosition = strpos($customMappingJson, '{% block params %}') + 18;
        $endTagPosition = strpos($customMappingJson, '{% endblock %}');
        $customMappingJson = substr($customMappingJson, $startTagPosition, $endTagPosition - $startTagPosition); // remove twig tags
        $customMappingJson = rtrim($customMappingJson, ","); // remove trailing comma
        $customMappingJson = '{'.$customMappingJson.'}'; // make valid object
        $customMapping = json_decode($customMappingJson, true);
        if ($customMapping === null) {
            throw new ApplicationException("Error in component's mapping, check ".realpath($mappingFile));
        }
        return $customMapping;
    }

    public static function getMapping($rootDir)
    {
        $mapping = self::getMainMapping($rootDir);

        if (file_exists($rootDir.'/../../../../Resources/Elasticsearch/mapping.json')) {
            $customMapping = self::getCustomMapping($rootDir.'/../../../../Resources/Elasticsearch/mapping.json');
            $mapping['mappings']['jobs']['properties'] = array_merge($mapping['mappings']['jobs']['properties'], $customMapping);
        } elseif (file_exists($rootDir.'/../../../../Resources/views/Elasticsearch/mapping.json.twig')) {
            // deprecated format, remove soon
            $customMapping = self::getCustomMappingDeprecated($rootDir.'/../../../../Resources/views/Elasticsearch/mapping.json.twig');
            $mapping['mappings']['jobs']['properties'] = array_merge($mapping['mappings']['jobs']['properties'], $customMapping);
        }

        return $mapping;
    }
}
