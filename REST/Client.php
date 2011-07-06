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
    static  $version = '2.1.0';
    private $options = array();
    
    protected $fire_hook = array();
    protected $fetch_hook = array();

    protected $requests = 0;
    protected $loads = 0;
    protected $loads_null = 0;
    protected $fetchs = 0;
    protected $fetchs_null = 0;
    protected $pulls = 0;
    protected $pulls_null = 0;
    protected $time = 0;
    
    /**
     * REST_Client factory that can be used to create REST_Client_Async or REST_Client_Sync instances.
     * @return REST_Client
     */
    public static function factory($type = 'sync', $options = array())
    {
        $class_name = 'REST_Client_'.ucfirst($type);

        if (class_exists($class_name, false)) {
            $instance = new $class_name($options);
        }
        else {
            $file = strtr($class_name, '_', '/').'.php';
            $paths = explode(PATH_SEPARATOR, ini_get('include_path'));
            foreach ($paths as $path) {
                $fullpath = $path . '/' . $file;
                if (file_exists($fullpath)) {
                    include_once($fullpath);
                    if (class_exists($class_name, false)) {
                        $instance = new $class_name($options);
                    }
                    break;
                }
            }
        }
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
     * Register a fire hook
     * @param callback
     * @param REST_Client
     */
    public function addFireHook($callback)
    {
        if (!is_callable($callback)) {
            throw Exception('FireHook callback is not callable');
        }
        if (!in_array($callback, $this->fire_hook)) {
            $this->fire_hook[] = $callback;
        }
        return $this;
    }
    
    /**
     * Register a fetch hook
     * @param callback
     * @param REST_Client
     */
    public function addFetchHook($callback)
    {
        if (!is_callable($callback)) {
            throw Exception('FetchHook callback is not callable');
        }
        if (!in_array($callback, $this->fetch_hook)) {
            $this->fetch_hook[] = $callback;
        }
        return $this;
    }    

    /**
     * Launch a request
     * @param  array
     * @return integer the request identifier
     */
    abstract public function fire(REST_Request $request);
    
    /**
     * Get a request response (after a fire)
     * @return REST_Response
     */
    abstract public function fetch();

    /**
     * Check if fire queue is overflowed 
     * @return boolean
     */
    abstract public function overflow();

    /**
     * Get some stats
     * @return mixed
     */
    public function getInfo($k = null)
    {
        $t = microtime(true) - $this->time;
        $a =  array(
            'requests'      => $this->requests,
            'requests_avg'  => $this->loads === 0 ? 0 : round($this->requests/$this->loads, 2),
            'requests_sec'  => $t === 0 ? 0 : round($this->requests/$t, 2),
            'fetchs_hit'    => $this->fetchs === 0 ? 0 : round(($this->fetchs - $this->fetchs_null) / $this->fetchs, 2),
            'pulls_hit'     => $this->pulls === 0 ? 0 : round(($this->pulls - $this->pulls_null) / $this->pulls, 2),
            'loads_hit'     => $this->loads === 0 ? 0 : round(($this->loads - $this->loads_null) / $this->loads, 2),
            'time'          => round($t, 2),
        );
        if (is_null($k) or !isset($a[$k])) 
            return $a;
        else 
            return $a[$k];
    }


}
