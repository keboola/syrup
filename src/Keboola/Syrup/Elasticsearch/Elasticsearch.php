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

    public static function getMapping($rootDir)
    {
        $template = file_get_contents($rootDir.'/Resources/Elasticsearch/mapping_template.json');

        if (file_exists($rootDir.'/../../../../Resources/Elasticsearch/mapping.json')) {
            $customTemplate = file_get_contents($rootDir . '/../../../../Resources/Elasticsearch/mapping.json');
        } elseif (file_exists($rootDir.'/../../../../Resources/views/Elasticsearch/mapping.json')) {
            // deprecated location
            $customTemplate = file_get_contents($rootDir.'/../../../../Resources/views/Elasticsearch/mapping.json');
        } elseif (file_exists($rootDir.'/../../../../Resources/views/Elasticsearch/mapping.json.twig')) {
            // deprecated format
            $customTemplate = file_get_contents($rootDir . '/../../../../Resources/views/Elasticsearch/mapping.json.twig');
            $startTagPosition = strpos($customTemplate, '{% block params %}') + 18;
            $endTagPosition = strpos($customTemplate, '{% endblock %}');
            $customTemplate = substr($customTemplate, $startTagPosition, $endTagPosition - $startTagPosition);
        } else {
            $customTemplate = file_get_contents($rootDir.'/Resources/Elasticsearch/mapping_default.json');
        }

        $mappingJson = str_replace('"params": {},', $customTemplate, $template);

        $json = json_decode($mappingJson, true);
        if ($json === null) {
            throw new ApplicationException("Error in mapping, check your mapping syntax");
        }

        return $json;
    }
}
