<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 encoding=utf-8 fdm=marker :

ini_set('include_path', dirname(__FILE__).'/../'.PATH_SEPARATOR.ini_get('include_path'));

require_once 'PHPUnit/Framework.php';
require_once 'REST/Puller.php';
require_once 'REST/Request.php';

class REST_PullerTest extends PHPUnit_Framework_TestCase
{
    function test_get()
    {
        $p = new REST_Puller(array(
            'max_clients' => 2,
        ));
        $r = new REST_Request('fr.php.net');
        $dom     = $p->fire($r->get('/dom'));
        $curl    = $p->fire($r->get('/curl'));
        $strings = $p->fire($r->get('/strings'));
        $pcre    = $p->fire($r->get('/pcre'));
        $xml     = $p->fire($r->get('/xml'));
        $ftp     = $p->fire($r->get('/ftp'));
        $sockets = $p->fire($r->get('/sockets'));

        $z = array();
        while(list($id, $response) = $p->fetch()) {
            $z[$id] = $response->content;
        }
        $this->assertContains('PHP: cURL - Manual', $z[$curl]);
        $this->assertContains('PHP: DOM - Manual',  $z[$dom]);
        $this->assertContains('id="book.strings"', $z[$strings]);
        $this->assertContains('PHP: PCRE - Manual', $z[$pcre]);
        $this->assertContains('id="book.xml"', $z[$xml]);
        $this->assertContains('PHP: FTP - Manual', $z[$ftp]);
        $this->assertContains('PHP: Sockets - Manual', $z[$sockets]);
    }
    function test_huge()
    {
        $p = new REST_Puller(array(
            'max_clients' => 100,
        ));
        $r = new REST_Request('localhost', 8000);
        for($i= 0;$i < 2500; $i++) {
            $p->fire($r->get('/'));
        }

        while(list(, $h) = $p->fetch()) {
            $this->assertEquals(200, $h->code);
        }
    }

}
