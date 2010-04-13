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
    private $options;
    private $handle;
    private $host;

    function __construct($host, $port = 80, $options = array())
    {
        $this->host = $host;
        $this->options = array(
            CURLOPT_PORT           => $port,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADER         => true,
            CURLOPT_USERAGENT      => 'REST_Client',
            //            CURLOPT_MAXREDIRS      => 0,
            //            CURLOPT_HEADER         => false,
            //            CURLOPT_FOLLOWLOCATION => true,
            //            CURLOPT_ENCODING       => "",
            //            CURLOPT_USERAGENT      => "spider",
            //            CURLOPT_AUTOREFERER    => true,
            //            CURLOPT_CONNECTTIMEOUT => 120,
            //            CURLOPT_TIMEOUT        => 120,
            //            CURLOPT_POST            => 1,
            //            CURLOPT_POSTFIELDS     => $curl_data,
            //            CURLOPT_SSL_VERIFYHOST => 0,
            //            CURLOPT_SSL_VERIFYPEER => false,
            //            CURLOPT_VERBOSE        => 1
        ) + $options;
    }

    public function get($upath)
    {
        if (!is_resource($this->handle))
            $this->handle = curl_init();

        $options = array(
            CURLOPT_URL => $this->upath2url($upath),
        );
        curl_setopt_array($this->handle, $this->options + $options);

        return $this->send();
    }

    public function delete($upath)
    {
        if (!is_resource($this->handle))
            $this->handle = curl_init();

        $options = array(
            CURLOPT_URL           => $this->upath2url($upath),
            CURLOPT_CUSTOMREQUEST => 'DELETE',
        );
        curl_setopt_array($this->handle, $this->options + $options);

        return $this->send();
    }


    public function post($upath, $data, $params = array())
    {
        if (!is_resource($this->handle))
            $this->handle = curl_init();
        $options = array(
            CURLOPT_URL             => $this->upath2url($upath, $params),
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => $data,
        );

        curl_setopt_array($this->handle, $this->options + $options);

        return $this->send();
    }

    public function put($upath, $data, $params = array())
    {
        if (!is_resource($this->handle))
            $this->handle = curl_init();

        $options = array(
            CURLOPT_URL             => $this->upath2url($upath, $params),
            CURLOPT_CUSTOMREQUEST   => 'PUT',
            CURLOPT_POSTFIELDS      => $data,
        );

        curl_setopt_array($this->handle, $this->options + $options);

        return $this->send();
    }


    protected function send()
    {
        $r = new REST_Response(curl_exec($this->handle), curl_errno($this->handle));
        if ($r->isError()) {
            $r->error = curl_error($this->handle);
        }
        else {
            $r->code = curl_getinfo($this->handle, CURLINFO_HTTP_CODE);
            $r->type = curl_getinfo($this->handle, CURLINFO_CONTENT_TYPE);
        }
        curl_close($this->handle);
        return $r;
    }


    protected function upath2url($upath)
    {
        $h = 'http://'.$this->host.':'.$this->options[CURLOPT_PORT];
        if (strpos($upath, $h) === 0)
            return $upath;
        else
            return $h.$upath;
    }

}
