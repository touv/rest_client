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

    function test_sync()
    {
        $r = REST_Request::newInstance()
                ->setProtocol('http')->setHost($this->test_host)->setPort($this->test_port)
                ->setMethod('GET')->setUrl('/');
        $this->sync->fire($r);
        if ($resp = $this->sync->fetch()) {
            $this->assertEquals(200, $resp->code);
        }
        $this->assertEquals(1, $this->sync->getInfo('requests'));
    }
    
    function test_medium_sync()
    {
        $this->async->setOption('queue_size', 3);

        $r = REST_Request::newInstance()
                ->setProtocol('http')->setHost('fr.php.net')
                ->setHttpProxy($this->http_proxy);

        $dom     = $this->sync->fire($r->get('/dom'));
        $curl    = $this->sync->fire($r->get('/curl'));
        $strings = $this->sync->fire($r->get('/strings'));
        $pcre    = $this->sync->fire($r->get('/pcre'));
        $xml     = $this->sync->fire($r->get('/xml'));
        $ftp     = $this->sync->fire($r->get('/ftp'));
        $sockets = $this->sync->fire($r->get('/sockets'));

        $z = array();
        while($response = $this->sync->fetch()) {
            $z[$response->id] = $response->content;
        }

        $this->assertContains('PHP: cURL - Manual', $z[$curl]);
        $this->assertContains('PHP: DOM - Manual',  $z[$dom]);
        $this->assertContains('id="book.strings"', $z[$strings]);
        $this->assertContains('PHP: PCRE - Manual', $z[$pcre]);
        $this->assertContains('id="book.xml"', $z[$xml]);
        $this->assertContains('PHP: FTP - Manual', $z[$ftp]);
        $this->assertContains('PHP: Sockets - Manual', $z[$sockets]);

        $this->assertEquals(7, $this->sync->getInfo('requests'));
    }    

    function test_small_async()
    {
        $r = REST_Request::newInstance()
                ->setProtocol('http')->setHost($this->test_host)->setPort($this->test_port)
                ->setMethod('GET')->setUrl('/');
        $id = $this->async->fire($r);
        if ($resp = $this->async->fetch()) {
            $this->assertEquals(200, $resp->code);
        }
        $this->assertEquals(1, $this->async->getInfo('requests'));
    }

    function test_medium_async()
    {
        $this->async->setOption('queue_size', 3);

        $r = REST_Request::newInstance()
                ->setProtocol('http')->setHost('fr.php.net')
                ->setHttpProxy($this->http_proxy);

        $dom     = $this->async->fire($r->get('/dom'));
        $curl    = $this->async->fire($r->get('/curl'));
        $strings = $this->async->fire($r->get('/strings'));
        $pcre    = $this->async->fire($r->get('/pcre'));
        $xml     = $this->async->fire($r->get('/xml'));
        $ftp     = $this->async->fire($r->get('/ftp'));
        $sockets = $this->async->fire($r->get('/sockets'));

        $z = array();
        while($response = $this->async->fetch()) {
            $z[$response->id] = $response->content;
        }

        $this->assertContains('PHP: cURL - Manual', $z[$curl]);
        $this->assertContains('PHP: DOM - Manual',  $z[$dom]);
        $this->assertContains('id="book.strings"', $z[$strings]);
        $this->assertContains('PHP: PCRE - Manual', $z[$pcre]);
        $this->assertContains('id="book.xml"', $z[$xml]);
        $this->assertContains('PHP: FTP - Manual', $z[$ftp]);
        $this->assertContains('PHP: Sockets - Manual', $z[$sockets]);

        $this->assertEquals(7, $this->async->getInfo('requests'));
    }

}
