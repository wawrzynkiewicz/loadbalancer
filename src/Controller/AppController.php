<?php

namespace App\Controller;

use App\Service\LoadBalancer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class AppController extends AbstractController
{
    private LoadBalancer $loadBalancer;

    /**
     * AppController constructor.
     * @param LoadBalancer $loadBalancer
     */
    public function __construct(LoadBalancer $loadBalancer)
    {
        $this->loadBalancer = $loadBalancer;
    }

    /**
     * Handle request and use loadBalancer service to forward it
     * @param Request $request
     */
    public function view(Request $request)
    {
        //pass request via loadBalancer to final host
        $this->loadBalancer->handleRequest($request);

        return new Response('done.');
    }
}