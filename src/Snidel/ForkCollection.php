<?php
namespace Ackintosh\Snidel;

class ForkCollection
{
    /** @var \Ackintosh\Snidel\Fork[] */
    private $forks;

    /**
     * @param   \Ackintosh\Snidel\Fork[]
     */
    public function __construct($forks)
    {
        $this->forks = $forks;
    }

    /**
     * @return  array
     */
    public function toArray()
    {
        return array_map(
            function ($fork) {
                return $fork->getResult()->getReturn();
            },
            $this->forks
        );
    }
}
