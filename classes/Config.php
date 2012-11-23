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
    
     function __construct() {
         
     }
     
     function read(){
         
     }
     
     function getParam($key = null){
         if (isset($this->config[$key])){
             return $this->config[$key];
         }
         return null;
     }
}

?>
