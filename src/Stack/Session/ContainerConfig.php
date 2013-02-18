<?php

namespace Stack\Session;

use Pimple;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Session;

class ContainerConfig
{
    public function process(Pimple $container)
    {
        $container['session'] = $container->share(function ($container) {
            return new Session($container['session.storage']);
        });

        $container['session.storage.handler'] = $container->share(function ($container) {
            return new NativeFileSessionHandler($container['session.storage.save_path']);
        });

        $container['session.storage'] = $container->share(function ($container) {
            return new NativeSessionStorage(
                $container['session.storage.options'],
                $container['session.storage.handler']
            );
        });

        $container['session.storage.save_path'] = null;
        $container['session.storage.options'] = [];
        $container['session.default_locale'] = 'en';
        $container['session.cookie_params'] = [];
    }
}
