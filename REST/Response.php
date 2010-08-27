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

/**
 * Parse the HTTP Response
 *
 * @category  REST
 * @package   REST_Client
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2010 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/bsd-license.php BSD Licence
 */
class REST_Response
{
    static $properties = array(  
        'code'  => CURLINFO_HTTP_CODE,  
        'time'  => CURLINFO_TOTAL_TIME,  
        'length'=> CURLINFO_CONTENT_LENGTH_DOWNLOAD,  
        'type'  => CURLINFO_CONTENT_TYPE  
    );
    public $id = null;
    public $headers = array();
    public $content = '';
    public $code = 0;
    public $type;
    public $time;
    public $length;

    private $errno = CURLE_OK;
    public  $error = '';

    function __construct($http_response, $errno = false, $error = '') 
    {
        list($this->headers, $this->content) = self::parse_http_response($http_response);
        $this->errno = $errno;
        $this->error = $error;
    }

    public function isError() 
    {
        return $this->errno == CURLE_OK ? false : $this->errno;
    }
    
    public function errorMessage()
    {
        return $this->error;
    }    

    static function parse_http_response ($string) 
    {
        $headers = array();
        $content = '';
        $str = strtok($string, "\n");
        $h = null;
        while ($str !== false) {
            if ($h and trim($str) === '') {                
                $h = false;
                continue;
            }
            if ($h !== false and false !== strpos($str, ':')) {
                $h = true;
                list($headername, $headervalue) = explode(':', trim($str), 2);
                $headername = strtolower($headername);
                $headervalue = ltrim($headervalue);
                if (isset($headers[$headername])) 
                    $headers[$headername] .= ',' . $headervalue;
                else 
                    $headers[$headername] = $headervalue;
            }
            if ($h === false) {
                $content .= $str."\n";
            }
            $str = strtok("\n");
        }
        return array($headers, trim($content));
    } 

}



