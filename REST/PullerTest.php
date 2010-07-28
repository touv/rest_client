<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 encoding=utf-8 fdm=marker :

ini_set('include_path', dirname(__FILE__).'/../'.PATH_SEPARATOR.ini_get('include_path'));

require_once 'PHPUnit/Framework.php';
require_once 'REST/Puller.php';
require_once 'REST/Request.php';

class REST_PullerTest extends PHPUnit_Framework_TestCase
{
    private $p;
    function setUp()
    {
        $this->p = new REST_Puller(array(
//            'debug' => true,
        ));
    }
    function tearDown()
    {
//        var_dump($this->p->getInfo());
        $this->p = null;
    }

    function test_small()
    {
        $r = new REST_Request('localhost', 8000);
        $this->p->fire($r->get('/'));
        if (list(, $h) = $this->p->fetch()) 
            $this->assertEquals(200, $h->code);
        $this->assertEquals(1, $this->p->getInfo('requests'));
    }

    function test_medium()
    {
        $this->p->setOption('queue_size', 3);

        $r = new REST_Request('fr.php.net');
        $dom     = $this->p->fire($r->get('/dom'));
        $curl    = $this->p->fire($r->get('/curl'));
        $strings = $this->p->fire($r->get('/strings'));
        $pcre    = $this->p->fire($r->get('/pcre'));
        $xml     = $this->p->fire($r->get('/xml'));
        $ftp     = $this->p->fire($r->get('/ftp'));
        $sockets = $this->p->fire($r->get('/sockets'));

        $z = array();
        while(list($id, $response) = $this->p->fetch()) {
            $z[$id] = $response->content;
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

        $r = new REST_Request('localhost', 8000);
        for($i= 0;$i < $requests; $i++) {
            $this->p->fire($r->get('/'));
        }

        while(list(, $h) = $this->p->fetch()) {
            $this->assertEquals(200, $h->code);
        }

        $this->assertEquals($requests, $this->p->getInfo('requests'));
    }

    function test_huge()
    {
        $requests = 50000;
        $clients = 150;
        $this->p->setOption('queue_size', $clients);

        $r = new REST_Request('localhost', 8000);
        for($i= 0;$i < $requests; $i++) {
            $this->p->fire($r->get('/'));
            if ($i > $clients) if (list(, $h) = $this->p->fetch()) 
                $this->assertEquals(200, $h->code);
        }

        while(list(, $h) = $this->p->fetch()) {
            $this->assertEquals(200, $h->code);
        }

        $this->assertEquals($requests, $this->p->getInfo('requests'));
    }


}
