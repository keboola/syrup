<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Tests\Elasticsearch;

use Elasticsearch\Client;
use Keboola\Syrup\Elasticsearch\ComponentIndex;

class ComponentIndexTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ComponentIndex
     */
    private static $index;

    public static function setUpBeforeClass()
    {
        self::$index = new ComponentIndex(SYRUP_APP_NAME, 'devel', new Client(['hosts' => [SYRUP_ELASTICSEARCH_HOST]]));
    }

    public function testGetIndexPrefix()
    {
        $this->assertEquals('devel', self::$index->getIndexPrefix());
    }

    public function testMapping()
    {
        $mapping = ComponentIndex::buildMapping(__DIR__.'/../../../../app');
        $this->assertNotNull($mapping);
        $this->assertArrayHasKey('mappings', $mapping);
        $this->assertArrayHasKey('jobs', $mapping['mappings']);
        $this->assertArrayHasKey('properties', $mapping['mappings']['jobs']);
    }

    public function testGetMapping()
    {
        $mapping = self::$index->getMapping();
        $mapping = array_shift($mapping);

        $this->assertNotNull($mapping);
        $this->assertArrayHasKey('mappings', $mapping);
        $this->assertArrayHasKey('jobs', $mapping['mappings']);
        $this->assertArrayHasKey('properties', $mapping['mappings']['jobs']);
    }

    public function testHasProperty()
    {
        $this->assertTrue(self::$index->hasProperty('component'));
        $this->assertTrue(self::$index->hasProperty('command'));
        $this->assertFalse(self::$index->hasProperty('asdfgh'));
    }
}
