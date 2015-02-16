<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Tests\Elasticsearch;

use Keboola\StorageApi\Client;
use Keboola\StorageApi\ClientException;
use Monolog\Handler\TestHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Keboola\Syrup\Aws\S3\Uploader;
use Keboola\Syrup\Command\JobCommand;
use Keboola\Syrup\Exception\UserException;
use Keboola\Syrup\Listener\SyrupExceptionListener;
use Keboola\Syrup\Monolog\Formatter\JsonFormatter;
use Keboola\Syrup\Monolog\Processor\SyslogProcessor;
use Keboola\Syrup\Service\StorageApi\StorageApiService;

class ElasticsearchTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers Keboola\Syrup\Elasticsearch\Elasticsearch::getMapping
     */
    public function testElasticsearchMapping()
    {

        $mapping = \Keboola\Syrup\Elasticsearch\Elasticsearch::getMapping(__DIR__.'/../../../../app');
        $this->assertNotNull($mapping);
        $this->assertArrayHasKey('mappings', $mapping);
        $this->assertArrayHasKey('jobs', $mapping['mappings']);
        $this->assertArrayHasKey('properties', $mapping['mappings']['jobs']);
        $this->assertArrayHasKey('params', $mapping['mappings']['jobs']['properties']);
        $this->assertArrayHasKey('properties', $mapping['mappings']['jobs']['properties']['params']);
        $this->assertArrayHasKey('account', $mapping['mappings']['jobs']['properties']['params']['properties']);
    }
}
