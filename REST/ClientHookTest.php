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
    private $hook_flags = array();
    
    function setUp()
    {
        $this->sync    = REST_Client::factory('sync',  array('verbose' => false))
                            ->addFireHook(array($this,'fire_hook'))
                            ->addFetchHook(array($this,'fetch_hook'));
        $this->async   = REST_Client::factory('async', array('verbose' => false))
                            ->addFireHook(array($this,'fire_hook'))
                            ->addFetchHook(array($this,'fetch_hook'));

        if (getenv('http_proxy')) {
            $this->http_proxy = getenv('http_proxy');
        }
    }
    function tearDown()
    {
        $this->sync = $this->async = null;
    }

    function fire_hook($request, $request_id, $client)
    {
        $this->hook_flags[] = 'fire';
        return $request;
    }
    
    function fetch_hook($response, $request_id, $client)
    {
        $this->hook_flags[] = 'fetch';
        return $response;
    }
    
    function test_hook_sync()
    {
        $r = REST_Request::newInstance()
                ->setProtocol('http')->setHost($this->test_host)->setPort($this->test_port)
                ->setHttpProxy($this->http_proxy);

        $this->sync->fire($r->get('/'));
        if ($resp = $this->sync->fetch()) {
            $this->assertEquals(200, $resp->code);
        }
        $this->assertContains('fire', $this->hook_flags);
        $this->assertContains('fetch', $this->hook_flags);
    }
    
    function test_hook_async()
    {
        $r = REST_Request::newInstance()
                ->setProtocol('http')->setHost($this->test_host)->setPort($this->test_port)
                ->setHttpProxy($this->http_proxy);

        $this->async->fire($r->get('/'));
        if ($resp = $this->async->fetch()) {
            $this->assertEquals(200, $resp->code);
        }
        $this->assertContains('fire', $this->hook_flags);
        $this->assertContains('fetch', $this->hook_flags);
    }    
}
