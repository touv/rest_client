<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 encoding=utf-8 fdm=marker :

ini_set('include_path', dirname(__FILE__).'/../'.PATH_SEPARATOR.ini_get('include_path'));

require_once 'PHPUnit/Framework.php';
require_once 'REST/Puller.php';
require_once 'REST/Request.php';

class REST_PullerTest extends PHPUnit_Framework_TestCase
{

    private $test_host = 'localhost';
    private $test_port = 80;

    private $p;
    function setUp()
    {
        $this->p = REST_Puller::newInstance(array(
//                        'verbose' => true,
                    ));
    }
    function tearDown()
    {
//        var_dump($this->p->getInfo());
        $this->p = null;
    }

    function test_small()
    {
        $r = REST_Request::newInstance()
                ->setProtocol('http')->setHost($this->test_host)->setPort($this->test_port)
                ->setMethod('GET')->setUrl('/');
        $this->p->fire($r);
        if ($resp = $this->p->fetch()) {
            $this->assertEquals(200, $resp->code);
        }
        $this->assertEquals(1, $this->p->getInfo('requests'));
    }

    function test_medium()
    {
        $this->p->setOption('queue_size', 3);

        $r = REST_Request::newInstance()->setProtocol('http')->setHost('fr.php.net');
        $dom     = $this->p->fire($r->get('/dom'));
        $curl    = $this->p->fire($r->get('/curl'));
        $strings = $this->p->fire($r->get('/strings'));
        $pcre    = $this->p->fire($r->get('/pcre'));
        $xml     = $this->p->fire($r->get('/xml'));
        $ftp     = $this->p->fire($r->get('/ftp'));
        $sockets = $this->p->fire($r->get('/sockets'));

        $z = array();
        while($response = $this->p->fetch()) {
            $z[$response->id] = $response->content;
        }

        $this->assertContains('PHP: cURL - Manual', $z[$curl]);
        $this->assertContains('PHP: DOM - Manual',  $z[$dom]);
        $this->assertContains('id="book.strings"', $z[$strings]);
        $this->assertContains('PHP: PCRE - Manual', $z[$pcre]);
        $this->assertContains('id="book.xml"', $z[$xml]);
        $this->assertContains('PHP: FTP - Manual', $z[$ftp]);
        $this->assertContains('PHP: Sockets - Manual', $z[$sockets]);

        $this->assertEquals(7, $this->p->getInfo('requests'));
    }

    function test_large()
    {
        $requests = 2500;
        $clients = 30;
        $this->p->setOption('queue_size', $clients);

        $r = REST_Request::newInstance()
                ->setProtocol('http')->setHost($this->test_host)->setPort($this->test_port)
                ->setMethod('GET')->setUrl('/');
        for($i= 0; $i < $requests; $i++) {
            $this->p->fire($r);
        }

        while($response = $this->p->fetch()) {
            $this->assertEquals(200, $response->code);
        }

        $this->assertEquals($requests, $this->p->getInfo('requests'));
    }

    function test_huge()
    {
        $requests = 50000;
        $clients = 150;
        $this->p->setOption('queue_size', $clients);

        $r = REST_Request::newInstance()
                ->setProtocol('http')->setHost($this->test_host)->setPort($this->test_port)
                ->setMethod('GET')->setUrl('/');
        for($i= 0;$i < $requests; $i++) {
            $this->p->fire($r);
            if ($i > $clients) if ($response =  $this->p->fetch()) 
                $this->assertEquals(200, $response->code);
        }

        while($response = $this->p->fetch()) {
            $this->assertEquals(200, $response->code);
        }

        $this->assertEquals($requests, $this->p->getInfo('requests'));
    }


}
