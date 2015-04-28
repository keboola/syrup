<?php
namespace Keboola\Syrup\Tests\Service\Session;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

class PdoHandlertest extends WebTestCase
{
    public function testSession()
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $options = $container->getParameter('pdo.db_options');
        $table = $options['db_table'];
        $idColumn = $options['db_id_col'];

        /**
         * @var PdoSessionHandler $storage
         */
        $storage = $container->get('session.handler.pdo');

        /**
         * @var Registry $doctrine
         */
        $doctrine = $container->get('doctrine');

        $id = uniqid('SESS');

        $storage->open('', 'sid');
        $storage->read($id);
        $storage->write($id, 'data');
        $storage->close();
        $this->assertEquals(1, $doctrine->getConnection('default')->query(sprintf('SELECT COUNT(*) FROM %s', $table))->fetchColumn());


        $data = $doctrine->getConnection('default')->fetchAll(sprintf('SELECT * FROM %s', $table));
        foreach ($data as $sessionData) {
            $this->assertEquals($sessionData[$idColumn], $id);
        }
    
        $storage->open('', 'sid');
        $storage->read($id);
        $storage->destroy($id);
        $storage->close();
        $this->assertEquals(0, $doctrine->getConnection('default')->query(sprintf('SELECT COUNT(*) FROM %s', $table))->fetchColumn());

        $storage->open('', 'sid');
        $data = $storage->read($id);
        $storage->close();
        $this->assertSame('', $data, 'Destroyed session returns empty string');
    }
}
