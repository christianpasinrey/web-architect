<?php

namespace App\Services;

class AppCommandService
{
    protected $commands_queue;

    public function __construct()
    {
        $this->commands_queue = [];
    }

    public function addCommandToQueue($command)
    {
        $this->commands_queue[] = $command;
    }

    public function flushCommandsQueue()
    {
        $this->commands_queue = [];
    }

    public function executeCommandsQueue()
    {
        foreach ($this->commands_queue as $command) {
            exec($command, $output, $return_var);
            if ($return_var !== 0) {
                return ['error' => 'Failed to execute command: ' . $command];
            }
        }
        $this->flushCommandsQueue();
        return ['success' => 'All commands executed successfully'];
    }
}
