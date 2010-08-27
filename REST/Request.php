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

/**
 * REST_Request class
 *
 * @category  REST
 * @package   REST_Client
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @author    Stéphane Gully <stephane.gully@gmail.com>
 * @copyright 2010 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/bsd-license.php BSD Licence
 */
class REST_Request
{
    protected $curl_options = array();
    
    protected $method   = null;
    protected $protocol = null;
    protected $host     = null;
    protected $port     = null;
    protected $url      = null;
    protected $body     = null;
    
    protected $user     = null;
    protected $password = '';

    protected function __construct($curl_options = array())
    {
        $this->curl_options = $curl_options;
    }

    /**
     * Create a new puller instance
     * @return REST_Puller
     */
    public static function newInstance($options = array())
    {
        return new self($options);
    }

    /**
     * Setup a CURL specific option
     * @return REST_Puller
     */
    public function setCurlOption($k, $v)
    {
        $this->curl_options[$k] = $v;
        return $this;
    }

    /**
     * Setup CURL options
     * @return REST_Puller
     */
    public function setCurlOptions(array $a)
    {
        foreach($a as $k => $v)
            $this->setCurlOption($k, $v);
        return $this;
    }
    
    /**
     * Complète intelligement les attributs par leurs bonnes valeurs
     * @return REST_Puller
     */
    public function autoAttributes()
    {
        if (is_null($this->method)) {
            $this->method = 'GET';
        }
        if (is_null($this->protocol)) {
            $this->protocol = 'http';
        }        
        if (is_null($this->host)) {
            $this->host = 'localhost';
        }
        if (is_null($this->port)) {
            $this->port = ($this->protocol == 'https' ? 443 : 80);
        }
        if (is_null($this->url)) {
            $this->url = '/';
        }
        return $this;
    }

    /**
     * HTTP ou HTTPS
     * @return REST_Puller
     */
    public function setProtocol($v)
    {
        if (in_array(strtolower($v), array('http', 'https'))) {
            $this->protocol = $v;
        }
        return $this;
    }
    public function getProtocol()
    {
        return $this->protocol;
    }
    
    /**
     * Hostname
     * @return REST_Puller
     */
    public function setHost($v)
    {
        $this->host = (string)$v;
        return $this;
    }
    public function getHost()
    {
        return $this->host;
    }    
    
    /**
     * Port
     * @return REST_Puller
     */
    public function setPort($v)
    {
        $this->port = (integer)$v;
        return $this;
    }
    public function getPort()
    {
        return $this->port;
    }
    
    /**
     * Méthode HTTP
     * @return REST_Puller
     */
    public function setMethod($v)
    {
        $this->method = strtoupper($v);
        return $this;
    }
    public function getMethod()
    {
        return $this->method;
    }
    
    
    /**
     * Url
     * @return REST_Puller
     */
    public function setURL($v)
    {
        $this->url = (string)$v;
        return $this;
    }
    public function getURL()
    {
        return $this->url;
    }
    
    /**
     * HTTP body
     * @return REST_Puller
     */
    public function setBody($v)
    {
        $this->body = $v;
        return $this;
    }
    public function getBody()
    {
        return $this->body;
    }
    
    /**
     * Header HTTP
     * @return REST_Puller
     */
    public function addHeader($k, $v)
    {
        // TODO
        return $this;
    }
    public function getHeader($k)
    {
        // TODO
        return '';
    }
    public function getHeaders()
    {
        // TODO
        return array();
    }

    /**
     * Convert REST_Request data to CURL format
     * @return array
     */
    public function toCurl()
    {
        $this->autoAttributes();

        $options = $this->curl_options + array(
            CURLOPT_PORT           => $this->port,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADER         => true,
            //            CURLOPT_USERAGENT      => 'REST/1.0',
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
        );
        
        $options[CURLOPT_URL]           = $this->protocol.'://'.$this->host.':'.$this->port.$this->url;
        $options[CURLOPT_CUSTOMREQUEST] = $this->method;
        if (!is_null($this->user)) {
            $options[CURLOPT_USERPWD] = $this->user.':'.$this->password;
        }

        if (!is_null($this->body) and $this->body !== '') {
            $options[CURLOPT_POSTFIELDS] = $this->body;
        }
        
        if ($this->method === 'POST') {
            $options[CURLOPT_POST] = true;
        }
        
        return $options;
    }


    /**
     * Convertion des méthodes en méthodes HTTP
     * Equivalent de la combinaison ->setURL(...)->setMethod(...)
     * Ex: $r->delete('/maressource')
     *     $r->options('/maressource')
     * @return REST_Puller
     */
    public function __call($method, $arguments) 
    {
        if (count($arguments) === 0)
            return trigger_error(sprintf('%s::%s() expects at least 1 parameter, 0 given', __CLASS__, $method), E_USER_WARNING);

        if (!is_string($arguments[0]))
            return trigger_error(sprintf('%s::%s() expects parameter 1 to be string, %s given', __CLASS__, $method, gettype($arguments[0])), E_USER_WARNING);

        $url = trim($arguments[0]);
        if ($url === '')
            return trigger_error(sprintf('%s::%s() expects parameter 1 to be not empty', __CLASS__, $method), E_USER_WARNING);
        
        $this->setURL($url);
        $this->setMethod($method);
        $this->setBody(isset($arguments[1]) ? $arguments[1] : null);
        
        return $this;
    }

    /**
     * Authentification HTTP
     * @return REST_Puller
     */
    public function setAuth($user, $password)
    {
        $this->user     = (string)$user;
        $this->password = (string)$password;
        return $this;
    }
    public function getAuth($user, $password)
    {
        return array($this->user, $this->password);
    }    
    
    /**
     * Setup a HTTP proxy
     * @return REST_Puller
     */
    public function setHttpProxy($proxy)
    {
        $proxy = (string)trim(str_replace('http://','',$proxy));
        if (!empty($proxy)) {
            list($host, $port) = explode(':',$proxy);
            $this->setCurlOption(CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            $this->setCurlOption(CURLOPT_PROXY,     $host);
            $this->setCurlOption(CURLOPT_PROXYPORT, $port);
        }
        return $this;
    }
}
