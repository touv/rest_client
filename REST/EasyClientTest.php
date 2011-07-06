<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 encoding=utf-8 fdm=marker :

ini_set('include_path', dirname(__FILE__).'/../'.PATH_SEPARATOR.ini_get('include_path'));

require_once 'REST/EasyClient.php';

class REST_ClientTest extends PHPUnit_Framework_TestCase
{
    private $http_proxy = null;
    
    function setUp()
    {
        if (getenv('http_proxy')) {
            $this->http_proxy = getenv('http_proxy');
        }
    }

    function test_basic()
    {
        $response = REST_EasyClient::newInstance()->setHttpProxy('')->get('/');
        $this->assertEquals($response->code, 200);
        $this->assertContains('</html>', $response->content);
    }

    function test_retro()
    {
        $client = new REST_EasyClient('localhost');
        $response = $client->setHttpProxy('')->get('/');
        $this->assertEquals($response->code, 200);
        $this->assertContains('</html>', $response->content);
    }

    function test_get()
    {
        $response = REST_EasyClient::newInstance('fr.php.net')->setHttpProxy($this->http_proxy)->get('/curl');
        $this->assertEquals($response->code, 200);
        $this->assertContains('PHP: cURL - Manual', $response->content);
    }

}
