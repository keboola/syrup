<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Tests\Elasticsearch;

use Elasticsearch\Client;
use Keboola\Syrup\Elasticsearch\Index;

class IndexTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Index
     */
    private static $index;

    public static function setUpBeforeClass()
    {
        self::$index = new Index(SYRUP_APP_NAME, 'devel', new Client(['hosts' => [SYRUP_ELASTICSEARCH_HOST]]));
    }

    /**
     * @covers \Keboola\Syrup\Elasticsearch\Index::getIndexPrefix
     */
    public function testGetIndexPrefix()
    {
        $this->assertEquals('devel', self::$index->getIndexPrefix());
    }

    /**
     * @covers \Keboola\Syrup\Elasticsearch\Index::buildMapping
     */
    public function testMapping()
    {
        $mapping = Index::buildMapping(__DIR__.'/../../../../app');
        $this->assertNotNull($mapping);
        $this->assertArrayHasKey('mappings', $mapping);
        $this->assertArrayHasKey('jobs', $mapping['mappings']);
        $this->assertArrayHasKey('properties', $mapping['mappings']['jobs']);
    }

    /**
     * @covers \Keboola\Syrup\Elasticsearch\Index::getYearFromIndexName
     */
    public function testGetYearFromIndexName()
    {
        $this->assertEquals(2014, Index::getYearFromIndexName('prod_syrup_ex-twitter_2014_7'));
    }

    /**
     * @covers \Keboola\Syrup\Elasticsearch\Index::getVersionFromIndexName
     */
    public function testGetVersionFromIndexName()
    {
        $this->assertEquals(7, Index::getVersionFromIndexName('prod_syrup_ex-twitter_2014_7'));
    }

    /**
     * @covers \Keboola\Syrup\Elasticsearch\Index::findLastIndexName
     * @dataProvider resolutionData
     */
    public function testLastIndexNameResolution($expected, $indices)
    {
        $this->assertEquals($expected, Index::findLastIndexName($indices));
    }

    public function resolutionData()
    {
        return [
            ['syrup_prod_2014_3', ['syrup_prod_2014_2', 'syrup_prod_2014_3', 'syrup_prod_2014_1']],
            ['prod_syrup_ex-twitter_2014_10', ['prod_syrup_ex-twitter_2014_7', 'prod_syrup_ex-twitter_2014_10']],
        ];
    }
}
