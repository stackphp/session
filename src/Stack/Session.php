<?php

namespace Stack;

use Pimple;
use Stack\Session\ContainerConfig;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Cookie;

class Session implements HttpKernelInterface
{
    private $app;
    private $container;

    public function __construct(HttpKernelInterface $app, array $options = [])
    {
        $this->app = $app;
        $this->container = $this->setupContainer($options);
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $type) {
            return $this->app->handle($request, $type, $catch);
        }

        $session = $this->container['session'];
        $request->setSession($session);

        $cookies = $request->cookies;
        if ($cookies->has($session->getName())) {
            $session->setId($cookies->get($session->getName()));
        } else {
            $session->migrate(false);
        }

        $response = $this->app->handle($request, $type, $catch);

        if ($session && $session->isStarted()) {
            $session->save();
            $params = session_get_cookie_params();
            $cookie = new Cookie(
                $session->getName(),
                $session->getId(),
                0 === $params['lifetime'] ? 0 : time() + $params['lifetime'],
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
            $response->headers->setCookie($cookie);
        }

        return $response;
    }

    private function setupContainer(array $options)
    {
        $container = new Pimple();

        $config = new ContainerConfig();
        $config->process($container);

        foreach ($options as $name => $value) {
            $container[$name] = $value;
        }

        return $container;
    }
}
