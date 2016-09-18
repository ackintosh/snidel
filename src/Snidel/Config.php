<?php
namespace Ackintosh\Snidel;

class Config
{
    /** @var array */
    private $params;

    /**
     * @param   array   $params
     */
    public function __construct($params)
    {
        $this->params = $params;
    }

    /**
     * @param   string  $name
     * @return  mixed
     */
    public function get($name)
    {
        return $this->params[$name];
    }
}
