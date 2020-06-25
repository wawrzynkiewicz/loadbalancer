<?php

namespace App\Service;

use App\Exception\InvalidAlgorithmException;
use App\Exception\HostException;
use App\Host;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;
use Psr\Cache\InvalidArgumentException;

class LoadBalancer
{
    const ALGORITHM_ROUNDROBIN = 'roundrobin';
    const ALGORITHM_LOAD = 'load';
    const LOAD_LIMIT = 0.75;

    private array $hosts;
    private string $algorithm;
    private LoggerInterface $logger;
    private AbstractAdapter $cache;

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * LoadBalancer constructor.
     * Host's list definition could be done via dependecy injection on various ways
     * Possible optimizations:
     * - yaml configuration of initial host instances and algorithm
     * - REST interface to dynamically add/remove hosts and update their load values
     * - Move host validation to "upper" level
     * - Implement dynamically loadable algorithm "drivers"
     *
     * @param array $hosts
     * @param string $algorithm
     * @throws HostException
     * @throws InvalidAlgorithmException
     */
    public function __construct(array $hosts, string $algorithm)
    {
        //validate Hosts
        if(count($hosts) == 0)
        {
            throw new HostException('Invalid amount of hosts passed');
        }

        foreach ($hosts as $host)
        {
            if(!is_object($host))
            {
                throw new HostException(sprintf('Invalid value for host passed', $host));
            }
            if(!$host instanceof Host)
            {
                throw new HostException(sprintf('Invalid host instance passed: %s', get_class($host)));
            }
        }
        $this->hosts = $hosts;

        // validate loadbalancer algorithm
        if(($algorithm !== self::ALGORITHM_LOAD) && ($algorithm !== self::ALGORITHM_ROUNDROBIN))
        {
            throw new InvalidAlgorithmException(sprintf('Invalid loadbalancer algorithm %s provided', $algorithm));
        }
        $this->algorithm = $algorithm;

        //initialize cache
        //@TODO better to use REDIS, Memcached or APCu to access value, but this requires a more complex setup
        $this->cache = new FilesystemAdapter();
    }

    public function handleRequest(Request $request)
    {
        switch ($this->algorithm)
        {
            case self::ALGORITHM_ROUNDROBIN:
                $this->logger->debug(sprintf('LoadBalancer algorithm: %s selected', self::ALGORITHM_ROUNDROBIN));
                $host = $this->getNextHost();
                $host->handleRequest($request);
                break;
            case self::ALGORITHM_LOAD:
                $this->logger->debug(sprintf('LoadBalancer algorithm %s selected', self::ALGORITHM_LOAD));
                $host = $this->getLowLoadHost();
                $host->handleRequest($request);
                break;
        }
    }

    /**
     * Returns the next host of the host list, just rotating all hosts
     * Using cache driver to persist current host index
     *
     * @return Host
     */
    private function getNextHost(): Host
    {
        //initial call, return first host
        if($this->getLastHostIndex() == -1)
        {
            $this->logger->debug(sprintf('LoadBalancer initialized, using first host of list.'));
            $this->setLastHostIndex(0);
            return $this->hosts[0];
        }

        //if last host in list last time, return first
        $total = count($this->hosts);
        if($this->getLastHostIndex() == ($total-1))
        {
            $this->logger->debug(sprintf('LoadBalancer reached last host of list, resetting to first one.'));
            $this->setLastHostIndex(0);
            return $this->hosts[0];
        }

        //just return next host
        $lastHostIndex = $this->getLastHostIndex();
        $lastHostIndex = $lastHostIndex+1;

        $this->logger->debug(sprintf('LoadBalancer selecting next host #%d', $lastHostIndex));
        $this->setLastHostIndex($lastHostIndex);
        return $this->hosts[$lastHostIndex];
    }

    /**
     * Returns the host with the lowest load, if all hosts' load is above load limit of 0.75
     * otherwise it just returns first host with load limit below 0.75
     *
     * @return Host
     */
    private function getLowLoadHost(): Host
    {
        $loadIndex = [];

        //iterate through all available hosts
        foreach ($this->hosts as $index => $host)
        {
            /** @var Host $host */
            $load = $host->getLoad();
            if($load < self::LOAD_LIMIT)
            {
                //if host has lower load them limit, just use it
                $this->logger->debug(sprintf('LoadBalancer using host #%d with low load %f', $index, $load));
                return $host;
            }
            $loadIndex[$index] = $load;
        }

        //determine host with lowest load value
        //if several host do have same load, first one will be used
        $hostIndex = array_search(min($loadIndex), $loadIndex);
        $this->logger->debug(sprintf('LoadBalancer using host #%d with lowest load %f in host list', $hostIndex, min($loadIndex)));

        //returns host with lowest load
        return $this->hosts[$hostIndex];
    }

    /**
     * Retrieve last host of roundrobin algorithm from cache
     *
     * @return int
     * @throws InvalidArgumentException
     */
    private function getLastHostIndex()
    {
        //initialize cache
        $lastHost = $this->cache->getItem('app.loadbalancer.last_host');
        if(!$lastHost->isHit()) {
            return -1;
        }
        return $lastHost->get();
    }

    /**
     * Persist last host of roundrobin algorithm in cache
     *
     * @param int $index
     * @throws InvalidArgumentException
     */
    private function setLastHostIndex(int $index)
    {
        $lastHost = $this->cache->getItem('app.loadbalancer.last_host');
        $lastHost->set($index);
        $this->cache->save($lastHost);
    }
}