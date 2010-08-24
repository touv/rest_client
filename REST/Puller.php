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
/**
 * 
 *
 * @category  REST
 * @package   REST_Client
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @author    Stéphane Gully <stephane.gully@gmail.com>
 * @copyright 2010 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/bsd-license.php BSD Licence
 */
class REST_Puller
{
    protected $options = array(
        'queue_size'  => null,
        'fetch_delay' => 0,    
        'pull_delay'  => 0,
        'verbose'       => null,
    );
    protected $mh = null;
    protected $running = 0;
    protected $stack = array();
    protected $requests = 0;
    protected $responses = 0;
    protected $handles = 0;
    protected $loads = 0;
    protected $loads_null = 0;
    protected $fetchs = 0;
    protected $fetchs_null = 0;
    protected $pulls = 0;
    protected $pulls_null = 0;
    protected $flag = false;
    protected $time = 0;


    /**
     * Constructor
     * @param array
     */
    protected function __construct($options = array())
    {
        $this->options = array_merge($this->options, $options);

        $this->mh = curl_multi_init();
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
     * Destructor
     */
    function __destruct()
    {
        curl_multi_close($this->mh);
    }

    /**
     * setOption
     * @param string
     * @param mixed
     * @return boolean
     */
    public function setOption($name, $value)
    {
        if ($this->handles === 0) {
            $this->options[$name] = $value;
        } else {
            // TODO : balancer une exception mieux typée ?
            throw new Exception("Invalid option !");
        }
        return $this;
    }

    /**
     * init
     */
    protected function init()
    {
        settype($this->options['queue_size'], 'integer');
        settype($this->options['fetch_delay'], 'integer');
        settype($this->options['pull_delay'], 'integer');
        settype($this->options['verbose'], 'boolean');

        if ($this->options['queue_size'] <= 0) $this->options['queue_size'] = 10;
        if ($this->options['fetch_delay'] < 0) $this->options['fetch_delay'] = 0;
        if ($this->options['pull_delay'] < 0) $this->options['pull_delay'] = 0;

        $this->time = microtime(true);
    }

    /**
     * Lancement d'une requete
     * @param  array
     * @return integer
     */
    public function fire($c)
    {
        if ($this->handles === 0) $this->init();
        $this->stack[++$this->handles] = array(clone $c, null, null);
        $this->tick();
        return $this->handles;
    }


    /**
     * Recupére une requete terminée
     * @return REST_Response or false
     */
    public function fetch()
    {
        do {
            ++$this->fetchs;
            foreach($this->stack as $k => $value) {
                if (is_null($value[2])) continue;
                $this->stack[$k][2] =  null;
                $this->stack[$k] = null;
                unset($this->stack[$k]);
                return $value[2]; // returns REST_Response instance
            }
            ++$this->fetchs_null;
            if ($this->options['fetch_delay'] > 0)
                usleep($this->options['fetch_delay']);
        } while ($this->tick(true));
        return false;
    }

    static function convert($size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }

    /**
     * tick
     * @return boolean
     */
    protected function tick($fetch = false)
    {
//        if ($this->options['verbose']) 
//            echo "MUSE : ".self::convert(memory_get_usage(true)).'('.(sizeof($this->stack) === $this->handles ? '+' : '-').')'."\n";
        if ($this->requests !== 0 and $this->handles === $this->requests and $this->requests === $this->responses) return false;
        $c = 0;
        if ($this->flag === false) {
            ++$this->loads;
            if (($this->handles - $this->requests) >= $this->options['queue_size']
                or ($this->requests !== 0 and  $this->requests === $this->responses)
                or $fetch === true) {
                if ($this->options['verbose']) echo 'LOAD : '.sprintf('%04d|%04d|%04d',$this->handles, $this->requests, $this->responses).' {';
                foreach($this->stack as &$value) if (!is_null($value[0])) {
                    if ($c >= $this->options['queue_size']) break;
                    $value[1] = curl_init();
                    curl_setopt_array($value[1], $value[0]->toCurl());
                    curl_multi_add_handle($this->mh, $value[1]);
                    $status = curl_multi_exec($this->mh, $this->running);
                    $value[0] = null; // to save some memory, removes REST_Request instance from the stack
                    ++$c;
                    ++$this->requests;
                    if ($this->options['verbose']) echo '-';
                }
                if ($this->options['verbose']) echo '} '.$c."\n";
                $this->flag = true;
            }
            else {
                ++$this->loads_null;
            }
        }
        else {
            ++$this->pulls;
            if ($this->options['pull_delay'] > 0)
                usleep($this->options['pull_delay']);
            $flush = ($this->running > $this->options['queue_size']);
            $r = $this->running;
            if ($this->options['verbose']) echo 'PULL : '.sprintf('%04d|%04d|%04d',$this->handles, $this->requests, $this->responses).' '.($flush ? '[' : '<');
            do {
                while(($status = curl_multi_exec($this->mh, $this->running)) == CURLM_CALL_MULTI_PERFORM);
                if ($status == CURLM_OK) {
                    $c += $this->_responses();
                }
                elseif ($this->options['verbose']) echo 'X';
                if (!$flush) break;
            } while ($this->running);
            if ($this->running == 0) $this->flag = false;
            if ($this->options['verbose']) echo ($flush ? ']' : '>').' '.$c.'/'.$r.PHP_EOL;
            if ($c === 0) ++$this->pulls_null;
        }
        return true;
    }

    private function _responses()
    {
        $c = 0;
        while($done = curl_multi_info_read($this->mh)) {
            ++$c;
            $f = false;
            foreach($this->stack as $k => &$value) {
                if (is_null($value[1]) or $value[1] !== $done['handle']) continue;
                $value[2] = new REST_Response(curl_multi_getcontent($done['handle']));
                foreach(REST_Response::$properties as $name => $const) {
                    $value[2]->$name = curl_getinfo($done['handle'], $const);
                }
                $value[2]->id = $k; // REST_Puller internal request identifier 
                if ($this->options['verbose']) echo ':';
                $f = true;
                break;
            }
            if ($this->options['verbose'] and !$f) echo '.';

            curl_multi_remove_handle($this->mh, $done['handle']);
            curl_close($done['handle']);
            $this->stack[$k][1] = null;
            ++$this->responses;
        }
        return $c;
    }


    public function getInfo($k = null)
    {
        $t = microtime(true) - $this->time;
        $a =  array(
            'requests'      => $this->requests,
            'requests_avg'  => round($this->requests/$this->loads, 2),
            'requests_sec'  => round($this->requests/$t, 2),
            'fetchs_hit'    => round(($this->fetchs - $this->fetchs_null) / $this->fetchs, 2),
            'pulls_hit'     => round(($this->pulls - $this->pulls_null) / $this->pulls, 2),
            'loads_hit'     => round(($this->loads - $this->loads_null) / $this->loads, 2),
            'time'          => round($t, 2),
        );
        if (is_null($k) or !isset($a[$k])) return $a;
        else return $a[$k];
    }
}



