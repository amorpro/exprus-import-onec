<?php
/**
 * Created by PhpStorm.
 * User: AmorPro
 * Date: 08.07.2018
 * Time: 18:50
 */

namespace Plugin;


abstract class Plugin
{
    public function on($event, $arguments)
    {
        $method = 'on' . $event;
        if(method_exists($this, $method)){
            call_user_func_array([$this, $method ], $arguments);
            return true;
        }

        return false;
    }

}