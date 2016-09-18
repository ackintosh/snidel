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
        $this->params['ownerPid'] = getmypid();
        $this->params['taskQueue'] = array(
            'className'         => '\Ackintosh\Snidel\Task\Queue',
            'constructorArgs'   => null,
        );
        $this->params['resultQueue'] = array(
            'className'         => '\Ackintosh\Snidel\Result\Queue',
            'constructorArgs'   => null,
        );
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
