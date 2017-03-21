<?php
namespace Ackintosh\Snidel;

class Config
{
    /** @var array */
    private $params;

    /**
     * @param   array   $params
     */
    public function __construct($params = [])
    {
        $default = [
            'concurrency'   => 5,
            'taskQueue'     => [
                'className'         => '\Ackintosh\Snidel\Task\Queue',
                'constructorArgs'   => null,
            ],
            'resultQueue'   => [
                'className'         => '\Ackintosh\Snidel\Result\Queue',
                'constructorArgs'   => null,
            ],
        ];

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
