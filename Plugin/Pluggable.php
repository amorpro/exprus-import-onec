<?php
/**
 * Created by PhpStorm.
 * User: AmorPro
 * Date: 08.07.2018
 * Time: 18:49
 */

namespace Plugin;

trait Pluggable
{
    /**
     * @var Plugin[]
     */
    protected $plugins = [];


    public function addPlugin(Plugin $plugin)
    {
        $this->plugins[get_class($plugin)] = $plugin;

        return $this;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        if(strpos($name, '_trigger') === 0){
            $event = str_replace('_trigger', '', $name);
            foreach($this->plugins as $plugin){
                $plugin->on($event, $arguments);
            }
        }
    }
}