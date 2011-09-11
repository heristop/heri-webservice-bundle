<?php

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Heri\WebServiceBundle\Command\SyncCommand;
use Heri\WebServiceBundle\Entity\Sample;

require_once __DIR__ . '/../../../../../../app/AppKernel.php';
require_once __DIR__ . '/../../../../../../app/bootstrap.php.cache';

class ClientTest extends \PHPUnit_Framework_TestCase
{
    protected
        $application,
        $em,
        $kernel;
    
    public function __construct()
    {
        $kernel = new AppKernel('test', true);
        $kernel->boot();
        
        $this->em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $this->kernel = $kernel;
    }
    
    public function testInsert()
    {
        $em = $this->em;

        $sample = new Sample();
        $sample->setLabel('heristop');
        $em->persist($sample);
        $em->flush();
        
        $this->assertEquals($this->countRecordToUpdate(), 1);
    }
    
    public function testWsConnection()
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        // register the command to test
        $application->add(new SyncCommand());

        $command = $application->find('webservice:load');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'service'  => 'Sample',
            '--list'   => true
        ));
        
        $this->assertRegExp('/addSample/', $commandTester->getDisplay());
    }
    
    public function testWsSynchronization()
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        // register the command to test
        $application->add(new SyncCommand());

        $command = $application->find('webservice:load');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'service'  => 'Sample',
            '--record' => 1
        ));
        
        $this->assertRegExp('/Time execution in seconds:/', $commandTester->getDisplay());
    }
    
    public function testPostSynchronization()
    {
        $this->assertEquals($this->countRecordToUpdate(), 0);
    }
    
    protected function countRecordToUpdate()
    {
        $query = $this->em->createQuery(<<<EOF
        SELECT COUNT(s.id) FROM Heri\WebServiceBundle\Entity\Sample s
        WHERE s.toUpdate = 1
EOF
        );
        
        return $query->getSingleScalarResult();
    }
}