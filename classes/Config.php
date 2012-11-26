<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Config
 *
 * @author newadminaccount
 */
class Config {

    //put your code here
    private $config = array();
    private $filename = "eventbrite-ics.conf";

    function __construct() {
        
    }

    function setFileName($filename = null) {
        $this->filename = $filename;
    }
    
    function getFileName(){
        return $this->filename;
    }

    function read() {
        try {
            if (parse_ini_file($this->filename)) {
                $this->config = parse_ini_file($this->filename);
            }
        } catch (Exception $ex) {
            // We just ignore errors for now ...
        }
        return $this->config;
    }

    function write() {
        $this->write_php_ini($this->config, $this->filename);
    }

    function getParam($key = null) {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }
        return null;
    }

    function setConfig($config = null) {
        if ($config == null) {
            $this->config = $this->read();
        } else {
            $this->config = $config;
        }
    }

    function write_php_ini($array, $file) {
        $res = array();
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $res[] = "[$key]";
                foreach ($val as $skey => $sval) {
                    if (is_array($sval)) {
                        foreach ($sval as $i => $v) {
                            $res[] = "{$skey}[$i] = $v";
                        }
                    } else {
                        $res[] = "$skey = $sval";
                    }
                }
            }
            else
                $res[] = "$key = $val";
        }
        $dataToSave = implode("\r\n", $res);
        if (!$this->safefilerewrite($file, $dataToSave)) {
            throw new ErrorException("Can't write '" . $file . "'");
        }
    }

    function safefilerewrite($fileName, $dataToSave) {
        if ($fp = fopen($fileName, 'w')) {
            $startTime = microtime();
            do {
                $canWrite = flock($fp, LOCK_EX);
                // If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
                if (!$canWrite)
                    usleep(round(rand(0, 100) * 1000));
            } while ((!$canWrite) and ((microtime() - $startTime) < 1000));

            //file was locked so now we can store information
            if ($canWrite) {
                $return = fwrite($fp, $dataToSave);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
        return $return;
    }

}

?>
