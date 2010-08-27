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

require_once 'REST/Response.php';
require_once 'REST/Request.php';

/**
 * REST_Client factory.
 * Can be used to create REST_Client_Async and REST_Client_Sync instances.
 *
 * @category  REST
 * @package   REST_Client
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @author    Stéphane Gully <stephane.gully@gmail.com>
 * @copyright 2010 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/bsd-license.php BSD Licence
 */
abstract class REST_Client
{
    static  $version = '1.1';
    private $options = array();

    /**
     * REST_Client factory that can be used to create REST_Client_Async or REST_Client_Sync instances.
     * @return REST_Client
     */
    public static function factory($type = 'sync', $options = array())
    {
        $class_name = 'REST_Client_'.ucfirst($type);
        $path       = 'REST/Client/'.ucfirst($type).'.php';
        if (!file_exists($path)) {
            return false;
        }
        require_once $path;
        $instance = new $class_name($options);
        return $instance;
    }

    protected function __construct($options = array())
    {
        $this->options = $options;
    }
    public function __destruct()
    {
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
     * Retrieve one option value
     * @param string
     * @param mixed
     */
    public function getOption($name = null)
    {
        if (is_null($name)) {
            return $this->options;
        } else {
            return isset($this->options[$name]) ? $this->options[$name] : null;
        }
    }
    
    /**
     * Launch a request
     * @param  array
     * @return integer the request identifier
     */
    abstract public function fire(REST_Request $request);
    
    /**
     * Get a request response (after a fire)
     * @param  array
     * @return REST_Response
     */
    abstract public function fetch();

}
