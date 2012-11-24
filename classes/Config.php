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

    function read() {
        if (parse_ini_file($this->filename)) {
            $this->config = parse_ini_file($this->filename);
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

    function setConfig($config = null){
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
        if (! file_put_contents($file, $dataToSave)){
            throw new ErrorException("Can't write '" . $file . "'");
        }
    }

}

?>
