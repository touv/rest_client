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
    private $options;
    private $handle;
    private $host;
    private $base;

    function __construct($host, $port = 80, $options = array())
    {
        $this->host = $host;
        $this->base = 'http://'.$this->host.':'.$port;
        $this->options = array(
            CURLOPT_PORT           => $port,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADER         => true,
            CURLOPT_USERAGENT      => 'REST_Client/'.self::$version,
            //            CURLOPT_MAXREDIRS      => 0,
            //            CURLOPT_HEADER         => false,
            //            CURLOPT_FOLLOWLOCATION => true,
            //            CURLOPT_ENCODING       => "",
            //            CURLOPT_USERAGENT      => "spider",
            //            CURLOPT_AUTOREFERER    => true,
            //            CURLOPT_CONNECTTIMEOUT => 120,
            //            CURLOPT_TIMEOUT        => 120,
            //            CURLOPT_SSL_VERIFYHOST => 0,
            //            CURLOPT_SSL_VERIFYPEER => false,
            //            CURLOPT_VERBOSE        => 1
        ) + $options;
        $this->handle = curl_init();
    }

    function __destruct() 
    {
        curl_close($this->handle);
    }

    public function __call($method, $arguments) 
    {
        if (count($arguments) === 0)
            return trigger_error(sprintf('REST_Client::%s() expects at least 1 parameter, 0 given', $method), E_USER_WARNING);

        if (!is_string($arguments[0]))
            return trigger_error(sprintf('REST_Client::%s() expects parameter 1 to be string, %s given', $method, gettype($arguments[0])), E_USER_WARNING);

        $url = trim($arguments[0]);
        if ($url === '')
            return trigger_error(sprintf('REST_Client::%s() expects parameter 1 to be not empty', $method), E_USER_WARNING);

        if (!is_resource($this->handle))
            return trigger_error(sprintf('REST_Client::%s() cURL session was lost', $method), E_USER_ERROR);

        $method  = strtoupper($method);
        $data    = isset($arguments[1]) ? $arguments[1] : null;
        $options = array();

        var_export($method);
        var_export($data);

        if (strpos($url, $this->base) === false)
            $options[CURLOPT_URL] = $this->base.$url;
        else 
            $options[CURLOPT_URL] = $url;

        $options[CURLOPT_CUSTOMREQUEST] = $method;

        if (!is_null($data) and $data !== '')
            $options[CURLOPT_POSTFIELDS] = $data;
        if ($method === 'POST')
            $options[CURLOPT_POST] = true;

        curl_setopt_array($this->handle, $this->options + $options);

        $r = new REST_Response(curl_exec($this->handle), curl_errno($this->handle));
        if ($r->isError()) {
            $r->error = curl_error($this->handle);
        }
        else {
            $r->code = curl_getinfo($this->handle, CURLINFO_HTTP_CODE);
            $r->type = curl_getinfo($this->handle, CURLINFO_CONTENT_TYPE);
        }
        return $r;
    }

    public function setAuth($user, $password)
    {
        $this->options[CURLOPT_USERPWD] = $user.':'.$password;
        return $this;
    }
}
