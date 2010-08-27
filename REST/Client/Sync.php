<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 fdm=marker encoding=utf8 :
/**
 * REST_Client_Sync
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

require_once 'REST/Client.php';

/**
 * A synchronous REST_Client
 *
 * @category  REST
 * @package   REST_Client
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @author    Stéphane Gully <stephane.gully@gmail.com>
 * @copyright 2010 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/bsd-license.php BSD Licence
 */
class REST_Client_Sync extends REST_Client
{
    protected $options = array(
        'queue_size'  => 1,
        'verbose'     => null,
    );

    private $handle   = null;
    private $response = null;
    private static $request_id = 0;
    
    public function __construct($options = array())
    {
        $this->options = $options;
        $this->handle = curl_init();
    }

    public function __destruct()
    {
        curl_close($this->handle);
    }

    /**
     * Register one option
     * @param string
     * @param mixed
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
        return $this;
    }
    
    /**
     * Launch a synchrone request
     * returns the request identifier or false if fire is aborted
     * @param  array
     * @return integer or false
     */
    public function fire(REST_Request $request)
    {
        if ($this->loads === 0)
            $this->time = microtime(true);
        ++$this->loads;

        $this->request_id++;
        $request->setCurlOption(CURLOPT_USERAGENT, 'REST_Client/'.self::$version);
        
        // launch the fire hooks
        foreach($this->fire_hook as $hook) {
            $ret = call_user_func($hook, $request, $this->request_id, $this);
            // this hook want to stop the fire ?
            if ($ret === false) {
                ++$this->loads_null;
                return false;
            }
        }

        // configure curl client
        curl_setopt_array($this->handle, $request->toCurl());

        if (!is_resource($this->handle)) {
            ++$this->loads_null;
            return trigger_error(sprintf('%s::%s() cURL session was lost', __CLASS__, $method), E_USER_ERROR);
        }
        
        // send the request and create the response object
        $this->response = new REST_Response(curl_exec($this->handle), curl_errno($this->handle), curl_error($this->handle));
        ++$this->requests;

        if (!$this->response->isError()) {
            foreach(REST_Response::$properties as $name => $const) {
                $this->response->$name = curl_getinfo($this->handle, $const);
            }
        }
        $this->response->id = $this->request_id;

        return $this->response->id; // return a unique identifier
    }
    
    /**
     * Fetch the response
     * returns the response object or false if fetch is aborted
     * @param  array
     * @return REST_Response or false
     */
    public function fetch()
    {
        ++$this->fetchs;
        ++$this->pulls;

        $response = $this->response;
        $this->response = null;
        
        // launch the fetch hooks
        foreach($this->fetch_hook as $hook) {
            call_user_func($hook, $response, $response->id, $this);
        }
        
        return $response;
    }
    
}
