<?php
/**
 * Created by JetBrains PhpStorm.
 * User: martinhalamicek
 * Date: 14/11/14
 * Time: 09:25
 * To change this template use File | Settings | File Templates.
 */
namespace Keboola\Syrup\Tests\Job;

class IndexNameResolverTest extends \PHPUnit_Framework_TestCase
{

    public function testGetYearFromIndexName()
    {
        $this->assertEquals(2014, \Keboola\Syrup\Job\Metadata\IndexNameResolver::getYearFromIndexName('prod_syrup_ex-twitter_2014_7'));
    }

    public function testGetVersionFromIndexName()
    {
        $this->assertEquals(7, \Keboola\Syrup\Job\Metadata\IndexNameResolver::getVersionFromIndexName('prod_syrup_ex-twitter_2014_7'));
    }


    /**
     * @param $excpected
     * @param $indices
     * @dataProvider resolutionData
     */
    public function testLastIndexNameResolution($excpected, $indices)
    {
        $this->assertEquals($excpected, \Keboola\Syrup\Job\Metadata\IndexNameResolver::getLastIndexName($indices));
    }

    public function resolutionData()
    {
        return [
            ['syrup_prod_2014_3', ['syrup_prod_2014_2', 'syrup_prod_2014_3', 'syrup_prod_2014_1']],
            ['prod_syrup_ex-twitter_2014_10', ['prod_syrup_ex-twitter_2014_7', 'prod_syrup_ex-twitter_2014_10']],
        ];
    }
}
