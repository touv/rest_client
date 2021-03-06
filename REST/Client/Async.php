<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 fdm=marker encoding=utf8 :
/**
 * REST_Client_Async
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
 * An Asynchronous REST_Client
 *
 * @category  REST
 * @package   REST_Client
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @author    Stéphane Gully <stephane.gully@gmail.com>
 * @copyright 2010 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/bsd-license.php BSD Licence
 */
class REST_Client_Async extends REST_Client
{
    protected $options = array(
        'queue_size'  => null,
        'fetch_delay' => 0,    
        'pull_delay'  => 0,
        'verbose'     => null,
    );

    protected $mh = null;
    protected $running = 0;
    protected $stack = array();
    protected $handles = 0;
    protected $responses = 0;
    protected $waiting_responses = 0;
    protected $waiting_requests= 0;
    protected $flag = false;

    /**
     * Constructor
     * @param array
     */
    public function __construct($options = array())
    {
        $this->options = array_merge($this->options, $options);
        $this->mh      = curl_multi_init();
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        curl_multi_close($this->mh);
    }

    /**
     * Register one option
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
            throw new Exception("setOption is called too late !");
        }
        return $this;
    }

    /**
     * init
     */
    protected function init()
    {
        settype($this->options['queue_size'],  'integer');
        settype($this->options['fetch_delay'], 'integer');
        settype($this->options['pull_delay'],  'integer');
        settype($this->options['verbose'],     'boolean');

        if ($this->options['queue_size'] <= 0) $this->options['queue_size'] = 10;
        if ($this->options['fetch_delay'] < 0) $this->options['fetch_delay'] = 0;
        if ($this->options['pull_delay'] < 0)  $this->options['pull_delay'] = 0;

        $this->time = microtime(true);
    }

    /**
     * Launch an asynchrone request
     * returns the request identifier of false if fire has been aborted
     * @param  array
     * @return integer or false 
     */
    public function fire(REST_Request $request)
    {
        if ($this->handles === 0) $this->init();
        $this->handles++; // create a fresh internal request id

        // launch the fire hooks
        foreach($this->fire_hook as $hook) {
            $ret = call_user_func($hook, $request, $this->handles, $this);
            // this hook want to stop the fire ?
            if ($ret === false) {
                $this->handles--; // reset the internal request id because nothing has been fired
                return false;
            }
        }

        $this->stack[$this->handles] = array(clone $request, null, null);
        ++$this->waiting_requests;
        $this->tick();
        return $this->handles;
    }

    /**
     * Check for the finished request and return corresponding REST_Response
     * @return REST_Response or false if fetch is finished 
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
                --$this->waiting_responses;
                
                // launch the fetch hooks
                foreach($this->fetch_hook as $hook) {
                    call_user_func($hook, $value[2], $value[2]->id, $this);
                }

                return $value[2]; // returns REST_Response instance
            }
            ++$this->fetchs_null;
            if ($this->options['fetch_delay'] > 0)
                usleep($this->options['fetch_delay']);
        } while ($this->tick(true));
        return false;
    }

//    static function convert($size)
//    {
//        $unit=array('b','kb','mb','gb','tb','pb');
//        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
//    }

    /**
     * tick
     * @return boolean
     */
    protected function tick($fetch = false)
    {
//        if ($this->options['verbose']) 
//            echo "MUSE : ".self::convert(memory_get_usage(true)).'('.(sizeof($this->stack) === $this->handles ? '+' : '-').')'."\n";
        if ($this->handles === 0) return false; // do not fetch responses if no requests has be fired
        if ($this->requests !== 0 and $this->handles === $this->requests and $this->requests === $this->responses) return false;
        $c = 0;
        if ($this->flag === false) {
            ++$this->loads;
            if ($this->waiting_requests === 0) {
                $this->flag = true;
                ++$this->loads_null;
            }
            elseif ($fetch === true or $this->waiting_requests >= $this->options['queue_size']) {
                if ($this->options['verbose']) echo 'LOAD : '.sprintf('%04d|%04d|%04d',$this->handles, $this->requests, $this->responses).' {';
                foreach($this->stack as &$value) if (!is_null($value[0])) { 
                    if ($c >= $this->options['queue_size']) break;
                    $value[1] = curl_init();
                    curl_setopt_array($value[1], $value[0]->toCurl());
                    curl_multi_add_handle($this->mh, $value[1]);
                    $status = curl_multi_exec($this->mh, $this->running);
                    $value[0] = null; // to save some memory, removes REST_Request instance from the stack
                    ++$c;
                    --$this->waiting_requests;
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
            if ($this->running == 0 or $c == 0) $this->flag = false;
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
                $value[2] = new REST_Response(curl_multi_getcontent($done['handle']),
                                              $done['result'],
                                              curl_error($done['handle']));
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
            ++$this->waiting_responses;
        }
        return $c;
    }

     /**
     * Check if fire queue is overflowed 
     * @return boolean
     */
    public function overflow()
    {
        return ! $this->waiting_responses < $this->options['queue_size'];
    }

}



