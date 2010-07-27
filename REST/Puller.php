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
 * 
 *
 * @category  REST
 * @package   REST_Client
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2010 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/bsd-license.php BSD Licence
 */
class REST_Puller
{

    protected $options = array(
        'max_clients' => 5,
        'fetch_delay' => 200000,
        'debug'       => true,
    );

    protected $stack = array();

    protected $requests = 0;
    protected $responses = 0;
    protected $handles = 0;
    protected $mcurls = 0;

    protected $mh = null;
    protected $running = 0;


    /**
     * Constructor
     * @param array
     */
    function __construct($options = array())
    {
        $this->options = array_merge($this->options, $options);
        settype($this->options['max_clients'], 'integer');
        settype($this->options['fetch_delay'], 'integer');
        if ($this->options['max_clients'] <= 0) $this->options['max_clients'] = 5;
        if ($this->options['fetch_delay'] < 0) $this->options['fetch_delay'] = 200000;
        $this->mh = curl_multi_init();
    }

    /**
     * Lancement d'une requete
     * @param 
     * @return integer
     */
    public function fire($c)
    {
        $this->stack[++$this->handles] = array($c, null, null);
        $this->tick();
        return $this->handles;
    }


    /**
     * Recupére une requete terminée
     * @return array
     */
    public function fetch()
    {
        if ($this->options['debug']) echo "==== FETCH\n";
        do {
            foreach($this->stack as $k => $value) {
                if (is_null($value[2])) continue;
                $this->stack[$k][2] =  null;
                $this->stack[$k] = null;
                unset($this->stack[$k]);
                return array($k, $value[2]);
            }
            usleep($this->options['fetch_delay']);
        } while ($this->tick());
        return false;
    }

function convert($size)
 {
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
 }

    protected function tick()
    {

        echo sizeof($this->stack).' // '.$this->convert(memory_get_usage(true))."\n";
        if ($this->options['debug']) echo "+--- TICK: $this->handles / $this->requests / $this->responses ----------------------------------------------\n";
        if ($this->handles === $this->requests and $this->requests === $this->responses) {
            if ($this->options['debug']) echo "+--- FALSE\n";
            return false;
        }
        $c = 0;
        if ($this->running == 0) {
            foreach($this->stack as &$valueX) {
                if (is_null($valueX[0])) continue;
                $c++;
                if ($c < $this->options['max_clients'] and ($this->handles - $this->requests) >= $this->options['max_clients']) {
                    if ($this->options['debug']) echo "| PUSH\n";
                    continue;
                }

//                $this->mh = curl_multi_init();
                $a = $r = null;
                $c = 0;
                foreach($this->stack as &$value) if (!is_null($value[0]) and $c <= $this->options['max_clients']) {
                    $value[1] = curl_init();
                    curl_setopt_array($value[1], $value[0]);
                    curl_multi_add_handle($this->mh, $value[1]);
                    $value[0] = null;
                    $status = curl_multi_exec($this->mh, $this->running);
                    ++$c;
                    ++$this->requests;
                }
                if ($this->options['debug']) echo '| LOT #'.++$this->mcurls.' clients : #'.$c."\n";
                break;
            }
        }
        else {
            if ($this->options['debug']) echo "| RUNNING : $this->running\n";
            $flush = ($this->handles % $this->options['max_clients'] === 0 or ($this->handles - $this->requests) >= $this->options['max_clients']);
            if ($flush)
                if ($this->options['debug']) echo "| FLUSH\n";
            do {
                while(($status = curl_multi_exec($this->mh, $this->running)) == CURLM_CALL_MULTI_PERFORM);
                if ($status == CURLM_OK) {
                    $this->_responses();
                }
                else break;
            } while ($this->running and $flush);
            if ($this->running == 0) {
//                curl_multi_close($this->mh);
//                $this->mh = null;
//                $this->running = null;
                if ($this->options['debug']) echo '| LOT #'.$this->mcurls." done.\n";
            }
        }
        if ($this->options['debug']) echo "+--- TRUE\n";
        return true;
    }

    private function _responses()
    {
        while($done = curl_multi_info_read($this->mh)) {
            if ($this->options['debug']) echo '| '.(string)$done['handle']."(".$done['result'].") done.\n";
            foreach($this->stack as $k => &$value) {
                if (is_null($value[1]) or $value[1] !== $done['handle']) continue;
                $value[2] = new REST_Response(curl_multi_getcontent($done['handle']));
                foreach(REST_Response::$properties as $name => $const) {
                    $value[2]->$name = curl_getinfo($done['handle'], $const);
                }
                if ($this->options['debug']) echo "| Response id #".$k."(".$value[2]->code.") done.\n";
                break;
            }
            curl_multi_remove_handle($this->mh, $done['handle']);
            curl_close($done['handle']);
            $this->stack[$k] = null;
            ++$this->responses;
        }
    }
}



