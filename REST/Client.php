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
 * @copyright 2010 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/bsd-license.php BSD Licence
 */

require_once 'REST/Response.php';
require_once 'REST/Request.php';

/**
 * a simple REST Client in PHP
 *
 * @category  REST
 * @package   REST_Client
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2010 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/bsd-license.php BSD Licence
 */
class REST_Client
{
    static $version = '1.1';
    private $handle;
    public $request;

    function __construct($host, $port = 80, $options = array())
    {
        $this->request = new REST_Request(
            $host, 
            $port, 
            array(CURLOPT_USERAGENT => 'REST_Client/'.self::$version)+$options
        );
        $this->handle = curl_init();
    }

    function __destruct() 
    {
        curl_close($this->handle);
    }

    public function __call($method, $arguments) 
    {
        curl_setopt_array($this->handle, $this->request->__call($method, $arguments));

        if (!is_resource($this->handle))
            return trigger_error(sprintf('%s::%s() cURL session was lost', __CLASS__, $method), E_USER_ERROR);

        $r = new REST_Response(curl_exec($this->handle), curl_errno($this->handle));
        if ($r->isError()) {
            $r->error = curl_error($this->handle);
        }
        else foreach(REST_Response::$properties as $name => $const) {
            $r->$name = curl_getinfo($this->handle, $const);
        }
        return $r;
    }

    public function setAuth($user, $password)
    {
        $this->request->setAuth($user, $password);
        return $this;
    }
}