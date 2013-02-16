<?php

namespace functional;

use Pimple;
use Silex\Application;
use Stack\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/* Taken from silex SessionServiceProviderTest */
class SilexApplicationTest extends \PHPUnit_Framework_TestCase
{
    public function testWithSessionRoutes()
    {
        $app = new Application();

        $app['exception_handler']->disable();

        $app['session'] = $app->share(function ($app) {
            return $app['request']->getSession();
        });

        $app->get('/login', function () use ($app) {
            $app['session']->set('logged_in', true);

            return 'Logged in successfully.';
        });

        $app->get('/account', function () use ($app) {
            if (!$app['session']->get('logged_in')) {
                return 'You are not logged in.';
            }

            return 'This is your account.';
        });

        $app->get('/logout', function () use ($app) {
            $app['session']->invalidate();

            return 'Logged out successfully.';
        });

        $app = $this->sessionify($app);

        $client = new Client($app);

        $client->request('GET', '/login');
        $this->assertEquals('Logged in successfully.', $client->getResponse()->getContent());

        $client->request('GET', '/account');
        $this->assertEquals('This is your account.', $client->getResponse()->getContent());

        $client->request('GET', '/logout');
        $this->assertEquals('Logged out successfully.', $client->getResponse()->getContent());

        $client->request('GET', '/account');
        $this->assertEquals('You are not logged in.', $client->getResponse()->getContent());
    }

    public function testWithRoutesThatDoesNotUseSession()
    {
        $app = new Application();

        $app['exception_handler']->disable();

        $app->get('/', function () {
            return 'A welcome page.';
        });

        $app->get('/robots.txt', function () {
            return 'Informations for robots.';
        });

        $app = $this->sessionify($app);

        $client = new Client($app);

        $client->request('GET', '/');
        $this->assertEquals('A welcome page.', $client->getResponse()->getContent());

        $client->request('GET', '/robots.txt');
        $this->assertEquals('Informations for robots.', $client->getResponse()->getContent());
    }

    private function sessionify(HttpKernelInterface $app)
    {
        return new Session($app, [
            'session.storage' => Pimple::share(function () {
                return new MockFileSessionStorage();
            }),
        ]);
    }
}
