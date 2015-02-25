<?php
/**
 * Created by JetBrains PhpStorm.
 * User: martinhalamicek
 * Date: 14/11/14
 * Time: 09:25
 * To change this template use File | Settings | File Templates.
 */
namespace Keboola\Syrup\Tests\Job;

use Keboola\Syrup\Job\Metadata\IndexNameResolver;

class IndexNameResolverTest extends \PHPUnit_Framework_TestCase
{

    public function testGetYearFromIndexName()
    {
        $this->assertEquals(2014, IndexNameResolver::getYearFromIndexName('prod_syrup_ex-twitter_2014_7'));
    }

    public function testGetVersionFromIndexName()
    {
        $this->assertEquals(7, IndexNameResolver::getVersionFromIndexName('prod_syrup_ex-twitter_2014_7'));
    }

    /**
     * @param $expected
     * @param $indices
     * @dataProvider resolutionData
     */
    public function testLastIndexNameResolution($expected, $indices)
    {
        $this->assertEquals($expected, IndexNameResolver::getLastIndexName($indices));
    }

    public function testSortIndices()
    {
        $testData = [
            'syrup_prod_2014_2',
            'syrup_prod_2014_3',
            'syrup_prod_2014_1',
            'syrup_prod_2014_10',
            'syrup_prod_2014_6',
            'syrup_prod_2014_4'
        ];

        $expected = [
            'syrup_prod_2014_1',
            'syrup_prod_2014_2',
            'syrup_prod_2014_3',
            'syrup_prod_2014_4',
            'syrup_prod_2014_6',
            'syrup_prod_2014_10'

        ];

        $this->assertEquals($expected, IndexNameResolver::sortIndices($testData));
    }

    public function resolutionData()
    {
        return [
            ['syrup_prod_2014_3', ['syrup_prod_2014_2', 'syrup_prod_2014_3', 'syrup_prod_2014_1']],
            ['prod_syrup_ex-twitter_2014_10', ['prod_syrup_ex-twitter_2014_7', 'prod_syrup_ex-twitter_2014_10']],
        ];
    }
}
