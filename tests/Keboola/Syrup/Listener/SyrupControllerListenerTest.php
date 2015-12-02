<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Keboola\Syrup\Tests\Listener;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Keboola\Syrup\Controller\ApiController;
use Keboola\Syrup\Listener\SyrupControllerListener;

class SyrupControllerListenerTest extends WebTestCase
{

    /**
     * @covers \Keboola\Syrup\Listener\SyrupControllerListener::onKernelController
     */
    public function testListener()
    {
        $client = static::createClient();

        $request = Request::create('/syrup/run', 'POST');
        $request->headers->set('X-StorageApi-Token', SYRUP_SAPI_TEST_TOKEN);

        /** @var RequestStack $requestStack */
        $requestStack = $client->getContainer()->get('request_stack');
        $requestStack->push($request);

        $controller = new ApiController();
        $controller->setContainer($client->getContainer());
        $event = new FilterControllerEvent(self::$kernel, [$controller, 'runAction'], $request, HttpKernelInterface::MASTER_REQUEST);

        $this->assertEmpty(\PHPUnit_Framework_Assert::readAttribute($controller, 'componentName'));
        $listener = new SyrupControllerListener();
        $listener->onKernelController($event);
        $this->assertNotEmpty(\PHPUnit_Framework_Assert::readAttribute($controller, 'componentName'));
    }
}
