<?php

namespace App;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Host
{
    private string $address;

    private float $load;

    private LoggerInterface $logger;

    /**
     * Host constructor.
     * @param string $address
     * @param float $load
     * @param LoggerInterface $logger
     */
    public function __construct(string $address, float $load, LoggerInterface $logger)
    {
        $this->address = $address;
        $this->load = $load;
        $this->logger = $logger;
    }

    /**
     * @param float $load
     */
    public function setLoad(float $load)
    {
        $this->load = $load;
    }

    /**
     * @return float
     */
    public function getLoad(): float
    {
        return $this->load;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @param Request $request
     */
    public function handleRequest(Request $request): void
    {
        $message = sprintf('Request handled by host %s which has a current load of %f', $this->getAddress(), $this->getLoad());
        $this->logger->info($message);
    }
}