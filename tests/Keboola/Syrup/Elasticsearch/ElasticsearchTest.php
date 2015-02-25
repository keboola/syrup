<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Tests\Elasticsearch;

class ElasticsearchTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers \Keboola\Syrup\Elasticsearch\Elasticsearch::getMapping
     */
    public function testElasticsearchMapping()
    {
        $mapping = \Keboola\Syrup\Elasticsearch\Elasticsearch::getMapping(__DIR__.'/../../../../app');
        $this->assertNotNull($mapping);
        $this->assertArrayHasKey('mappings', $mapping);
        $this->assertArrayHasKey('jobs', $mapping['mappings']);
        $this->assertArrayHasKey('properties', $mapping['mappings']['jobs']);
    }
}
