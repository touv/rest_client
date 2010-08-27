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

    private $hook_flags = array();
    
    function setUp()
    {
        if (getenv('http_proxy')) {
            $this->http_proxy = getenv('http_proxy');
        }
    }
    function tearDown()
    {
    }
    
    function test_hook_sync()
    {
        $this->hook_flags = array();
        $rest = REST_Client::factory('sync',  array('verbose' => false))
                            ->addFireHook(array($this,'fire_hook'))
                            ->addFetchHook(array($this,'fetch_hook'));

        $r = REST_Request::newInstance()
                ->setProtocol('http')->setHost($this->test_host)->setPort($this->test_port);

        $rest->fire($r->get('/'));
        if ($resp = $rest->fetch()) {
            $this->assertEquals(200, $resp->code);
        }
        $this->assertContains('fire', $this->hook_flags);
        $this->assertContains('fetch', $this->hook_flags);
    }
    
    function test_hook_async()
    {
        $rest = REST_Client::factory('async',  array('verbose' => false))
                            ->addFireHook(array($this,'fire_hook'))
                            ->addFetchHook(array($this,'fetch_hook'));

        $r = REST_Request::newInstance()
                ->setProtocol('http')->setHost($this->test_host)->setPort($this->test_port);

        $rest->fire($r->get('/'));
        if ($resp = $rest->fetch()) {
            $this->assertEquals(200, $resp->code);
        }
        $this->assertContains('fire', $this->hook_flags);
        $this->assertContains('fetch', $this->hook_flags);
    }
    
    function test_multihook()
    {
        $this->hook_flags = array();
        $rest = REST_Client::factory('async',  array('verbose' => false))
                            ->addFireHook(array($this,'fire_hook'))
                            ->addFireHook(array($this,'fire_hook2'))
                            ->addFetchHook(array($this,'fetch_hook'))
                            ->addFetchHook(array($this,'fetch_hook2'));

        $r = REST_Request::newInstance()
                ->setProtocol('http')->setHost($this->test_host)->setPort($this->test_port);

        $rest->fire($r->get('/'));
        if ($resp = $rest->fetch()) {
            $this->assertEquals(200, $resp->code);
        }
        
        $this->assertContains('fire', $this->hook_flags);
        $this->assertContains('fire2', $this->hook_flags);
        $this->assertContains('fetch', $this->hook_flags);
        $this->assertContains('fetch2', $this->hook_flags);
    }
    
    function test_firehook_abort()
    {
        $this->hook_flags = array();
        $rest = REST_Client::factory('async',  array('verbose' => false))
                    ->addFireHook(array($this,'fire_hook_abort'));

        $r = REST_Request::newInstance()
                ->setProtocol('http')->setHost($this->test_host)->setPort($this->test_port);

        $rest->fire($r->get('/'));

        // no response because fire has been aborted
        $resp = $rest->fetch();
        $this->assertFalse($resp);
    }
    
    function test_hook_modify()
    {
        $this->hook_flags = array();
        $rest = REST_Client::factory('async',  array('verbose' => false))
                    ->addFireHook(array($this,'fire_hook_modify'))
                    ->addFetchHook(array($this,'fetch_hook_modify'));

        $r = REST_Request::newInstance()
                ->setProtocol('http')->setHost($this->test_host)->setPort($this->test_port);

        $rest->fire($r->get('/'));
        $resp = $rest->fetch();
        
        $this->assertEquals('/mymodifiedurl', $r->getUrl());
        $this->assertEquals(404, $resp->code);
    }       
    
    
    

    function fire_hook($request, $request_id, $client)
    {
        $this->hook_flags[] = 'fire';
        return true; // do not abort fire
    }
    
    function fetch_hook($response, $request_id, $client)
    {
        $this->hook_flags[] = 'fetch';
    }
    
    function fire_hook2($request, $request_id, $client)
    {
        $this->hook_flags[] = 'fire2';
        return true; // do not abort fire
    }
    
    function fetch_hook2($response, $request_id, $client)
    {
        $this->hook_flags[] = 'fetch2';
    }
    
    function fire_hook_abort($request, $request_id, $client)
    {
        return false; // abort fire
    }
    
    function fire_hook_modify($request, $request_id, $client)
    {
        $request->setUrl('/mymodifiedurl');
        return true; // do not abort fire
    }
    function fetch_hook_modify($response, $request_id, $client)
    {
        $response->content = 'my modified content';
    }    
}
