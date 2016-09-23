<?php
namespace Ackintosh\Snidel;

class Config
{
    /** @var array */
    private $params;

    /**
     * @param   array   $params
     */
    public function __construct($params = array())
    {
        $default = array(
            'concurrency'   => 5,
            'taskQueue'     => array(
                'className'         => '\Ackintosh\Snidel\Task\Queue',
                'constructorArgs'   => null,
            ),
            'resultQueue'   => array(
                'className'         => '\Ackintosh\Snidel\Result\Queue',
                'constructorArgs'   => null,
            ),
        );

        $this->params = array_merge($default, $params);
        $this->params['ownerPid'] = getmypid();
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
