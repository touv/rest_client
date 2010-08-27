<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 encoding=utf-8 fdm=marker :

ini_set('include_path', dirname(__FILE__).'/../'.PATH_SEPARATOR.ini_get('include_path'));

require_once 'PHPUnit/Framework.php';
require_once 'REST/Client.php';
require_once 'REST/Request.php';

class REST_ClientTest extends PHPUnit_Framework_TestCase
{

    private $test_host  = 'localhost';
    private $test_port  = 80;
    private $http_proxy = null;
    
    private $sync;
    private $async;
    
    function setUp()
    {
        $this->sync    = REST_Client::factory('sync',  array('verbose' => false));
        $this->async   = REST_Client::factory('async', array('verbose' => false));
        
        if (getenv('http_proxy')) {
            $this->http_proxy = getenv('http_proxy');
        }
    }
    function tearDown()
    {
        $this->sync = $this->async = null;
    }

    function test_large_async()
    {
        $requests = 2500;
        $clients = 30;
        $this->async->setOption('queue_size', $clients);

        $r = REST_Request::newInstance()
                ->setProtocol('http')->setHost($this->test_host)->setPort($this->test_port)
                ->setMethod('GET')->setUrl('/');
        for($i= 0; $i < $requests; $i++) {
            $this->async->fire($r);
        }

        while($response = $this->async->fetch()) {
            $this->assertEquals(200, $response->code);
        }

        $this->assertEquals($requests, $this->async->getInfo('requests'));
    }

    function test_huge_async()
    {
        $requests = 50000;
        $clients = 150;
        $this->async->setOption('queue_size', $clients);

        $r = REST_Request::newInstance()
                ->setProtocol('http')->setHost($this->test_host)->setPort($this->test_port)
                ->setMethod('GET')->setUrl('/');
        for($i= 0;$i < $requests; $i++) {
            $this->async->fire($r);
            if ($i > $clients) if ($response =  $this->async->fetch()) 
                $this->assertEquals(200, $response->code);
        }

        while($response = $this->async->fetch()) {
            $this->assertEquals(200, $response->code);
        }

        $this->assertEquals($requests, $this->async->getInfo('requests'));
    }


}
