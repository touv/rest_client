<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 encoding=utf-8 fdm=marker :

ini_set('include_path', dirname(__FILE__).'/../'.PATH_SEPARATOR.ini_get('include_path'));

require_once 'PHPUnit/Framework.php';
require_once 'REST/Client.php';

class REST_ClientTest extends PHPUnit_Framework_TestCase
{
    function test_get()
    {
        $c = new REST_Client('fr.php.net');
        $r = $c->get('/curl');
        $this->assertEquals($r->code, 200);
        $this->assertContains('PHP: cURL - Manual', $r->content);
    }
}
