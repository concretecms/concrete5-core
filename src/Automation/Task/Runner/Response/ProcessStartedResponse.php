<?php

namespace Concrete\Core\Automation\Task\Runner\Response;

use Concrete\Core\Entity\Automation\Process;

class ProcessStartedResponse implements ResponseInterface
{

    /**
     * @var string
     */
    protected $message;

    /**
     * @var Process
     */
    protected $process;

    public function __construct(Process $process, string $message)
    {
        $this->process = $process;
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return Process
     */
    public function getProcess(): Process
    {
        return $this->process;
    }


}
