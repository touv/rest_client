<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 encoding=utf-8 fdm=marker :

ini_set('include_path', dirname(__FILE__).'/../'.PATH_SEPARATOR.ini_get('include_path'));

require_once 'PHPUnit/Framework.php';
require_once 'REST/Client.php';

class REST_ClientTest extends PHPUnit_Framework_TestCase
{
    function test_get()
    {
        $client = REST_Client::newInstance();

        $request = REST_Request::newInstance()
                ->setProtocol('http')->setHost('fr.php.net')
                ->setMethod('GET')->setUrl('/curl');

        $response = $client->fire($request);
        $this->assertEquals($response->code, 200);
        $this->assertContains('PHP: cURL - Manual', $response->content);
    }
}
