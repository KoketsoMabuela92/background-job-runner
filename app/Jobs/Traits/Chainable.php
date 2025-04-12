<?php

namespace App\Jobs\Traits;

trait Chainable
{
    protected array $chain = [];

    /**
     * Add a job to the chain
     */
    public function chain(string $jobClass, string $method, array $parameters = [], ?int $priority = null): self
    {
        $this->chain[] = [
            'job_class' => $jobClass,
            'method' => $method,
            'parameters' => $parameters,
            'priority' => $priority
        ];

        return $this;
    }

    /**
     * Get the chain of jobs
     */
    public function getChain(): array
    {
        return $this->chain;
    }

    /**
     * Clear the chain
     */
    public function clearChain(): void
    {
        $this->chain = [];
    }
} 