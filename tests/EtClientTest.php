<?php

use CMCi\ExactTarget\EtClient;
use CMCi\ExactTarget\EtSubscriber;

class EtClientTest extends PHPUnit_Framework_TestCase
{
    public function testServerConfig()
    {
        $client = new EtClient('test', 'test', 's7');

        $this->assertEquals(get_class($client), 'CMCi\exactTarget\EtClient');
        $this->assertEquals($client->getServer(), 's4');

        $client->setServer('s6');

        $this->assertEquals($client->getServer(), 's6');
    }

    public function testBuildTriggeredSend()
    {
        $client = new EtClient('test', 'test', 's4');
        $ts     = $client->buildTriggeredSend('motherbrain');

        $this->assertEquals(get_class($ts), 'CMCi\exactTarget\EtTriggeredSend');
    }

    public function testCastMethod()
    {
        $blankClass                = new stdClass();
        $blankClass->EmailAddress  = 'support@cmcigroup.com';
        $blankClass->SubscriberKey = 'support@cmcigroup.com';
        $client                    = new EtClient('test', 'test', 's7');
        $subscriber                = $client->cast($blankClass, 'CMCi\exactTarget\EtSubscriber', $client);

        $this->assertEquals(get_class($subscriber), 'CMCi\ExactTarget\EtSubscriber');
        $this->assertEquals($subscriber->getEmailAddress(), 'support@cmcigroup.com');
    }
}
