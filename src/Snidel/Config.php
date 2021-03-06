<?php
declare(strict_types=1);

namespace Ackintosh\Snidel;

use Bernard\Driver\FlatFileDriver;

class Config
{
    /** @var array */
    private $params;

    public function __construct(array $params = [])
    {
        $default = [
            'concurrency'   => 5,
            'logger'        => null,
            'driver' => null,
            // a polling duration(in seconds) of queueing
            'pollingDuration' => 1,
        ];

        $this->params = array_merge($default, $params);
        $this->params['ownerPid'] = getmypid();
        $this->params['id'] = spl_object_hash($this);
        if (!$this->params['driver']) {
            $this->params['driver'] = new FlatFileDriver(
                sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->params['id']
            );
        }
    }

    /**
     * @return  mixed
     */
    public function get(string $name)
    {
        return $this->params[$name];
    }
}
