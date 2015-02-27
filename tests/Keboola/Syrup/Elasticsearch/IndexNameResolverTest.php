<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Tests\Elasticsearch;

use Keboola\Syrup\Elasticsearch\IndexNameResolver;

class IndexNameResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \Keboola\Syrup\Elasticsearch\IndexNameResolver::getYearFromIndexName
     */
    public function testGetYearFromIndexName()
    {
        $this->assertEquals(2014, IndexNameResolver::getYearFromIndexName('prod_syrup_ex-twitter_2014_7'));
    }

    /**
     * @covers \Keboola\Syrup\Elasticsearch\IndexNameResolver::getVersionFromIndexName
     */
    public function testGetVersionFromIndexName()
    {
        $this->assertEquals(7, IndexNameResolver::getVersionFromIndexName('prod_syrup_ex-twitter_2014_7'));
    }

    /**
     * @covers \Keboola\Syrup\Elasticsearch\IndexNameResolver::getLastIndex
     * @dataProvider resolutionData
     */
    public function testLastIndexNameResolution($expected, $indices)
    {
        $this->assertEquals($expected, IndexNameResolver::getLastIndexName($indices));
    }

    public function resolutionData()
    {
        return [
            ['syrup_prod_2014_3', ['syrup_prod_2014_2', 'syrup_prod_2014_3', 'syrup_prod_2014_1']],
            ['prod_syrup_ex-twitter_2014_10', ['prod_syrup_ex-twitter_2014_7', 'prod_syrup_ex-twitter_2014_10']],
        ];
    }
}
