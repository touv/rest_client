<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 fdm=marker encoding=utf8 :
/**
 * REST_Client
 *
 * Copyright (c) 2010, Nicolas Thouvenin
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the author nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE REGENTS AND CONTRIBUTORS ``AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE REGENTS AND CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  REST
 * @package   REST_Client
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @author    Stéphane Gully <stephane.gully@gmail.com>
 * @copyright 2010 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/bsd-license.php BSD Licence
 */

require_once 'REST/Request.php';
require_once 'REST/Client.php';

/**
 * REST_EasyClient 
 *
 * @category  REST
 * @package   REST_Client
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @author    Stéphane Gully <stephane.gully@gmail.com>
 * @copyright 2010 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/bsd-license.php BSD Licence
 */
class REST_EasyClient
{
    private $request;
    private $client;

    /**
     * Create a new easyclient instance
     * @return REST_EasyClient
     */
    public static function newInstance($host = 'localhost', $port = 80, $options = array())
    {
        return new self($host, $port, $options);
    }

    public function __construct($host = 'localhost', $port = 80, $options = array())
    {
        $this->request = REST_Request::newInstance()
            ->setProtocol('http')
            ->setHost($host)
            ->setPort($port)
            ->setCurlOptions($options);
        $this->client = REST_Client::factory('sync',  array('verbose' => false));
    }

    public function __destruct()
    {
        $this->request = null;
        $this->client = null;
    }
    
    public function __call($method, $arguments)
    {
        if (count($arguments) === 0)
            return trigger_error(sprintf('REST_EasyClient::%s() expects at least 1 parameter, 0 given', $method), E_USER_WARNING);

        if (!is_string($arguments[0]))
            return trigger_error(sprintf('REST_EasyClient::%s() expects parameter 1 to be string, %s given', $method, gettype($arguments[0])), E_USER_WARNING);

        $url = trim($arguments[0]);
        if ($url === '')
            return trigger_error(sprintf('REST_EasyClient::%s() expects parameter 1 to be not empty', $method), E_USER_WARNING);

        $this->request
            ->setMethod($method)
            ->setURL($url)
            ->setBody(isset($arguments[1]) ? $arguments[1] : null);

        $this->client->fire($this->request);
        return $this->client->fetch();
    }

    /**
     * Authentification HTTP
     * @return REST_EasyClient
     */
    public function setAuth($user, $password)
    {
        $this->request->setAuth($user, $password);
        return $this;
    }

    /**
     * Setup a HTTP proxy
     * @return REST_EasyClient
     */
    public function setHttpProxy($proxy)
    {
        $this->request->setHttpProxy($proxy);
        return $this;
    }
}
