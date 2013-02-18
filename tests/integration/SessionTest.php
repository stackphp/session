<?php

namespace integration;

use common\TestCase;
use Pimple;
use Stack\CallableHttpKernel;
use Stack\Session;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SessionTest extends TestCase
{
    const SIMULATED_TIME = 1337882841;

    public function testDefaultSetsNoCookies()
    {
        $app = new CallableHttpKernel(function (Request $request) {
            return new Response('test');
        });

        $client = new Client($app);

        $client->request('GET', '/');

        $this->assertEquals('test', $client->getResponse()->getContent());

        $cookies = $client->getResponse()->headers->getCookies();
        $this->assertCount(0, $cookies);
    }

    public function testDefaultSessionParams()
    {
        $app = new CallableHttpKernel(function (Request $request) {
            $request->getSession()->set('some_session_var', 'is set');

            return new Response('test');
        });

        $app = $this->sessionify($app);

        $client = new Client($app);

        $client->request('GET', '/');

        $this->assertEquals('test', $client->getResponse()->getContent());

        $cookies = $client->getResponse()->headers->getCookies();
        $this->assertCount(1, $cookies);

        $cookie = $cookies[0];
        $expectedCookie = new Cookie(
            $this->mockFileSessionStorage->getName(),
            $this->mockFileSessionStorage->getId(),
            0,
            '/',
            '',
            false,
            false
        );

        $this->assertEquals($expectedCookie, $cookie);

        $bag = $this->mockFileSessionStorage->getBag('attributes');
        $this->assertEquals('is set', $bag->get('some_session_var'));
    }

    /** @dataProvider provideOverrideSessionParams */
    public function testOverrideSessionParams($expectedDomain, $expectedPath, $expectedSecure, $expectedHttponly, $expectedExpire, $config)
    {
        $app = new CallableHttpKernel(function (Request $request) {
            $request->getSession()->set('some_session_var', 'is set');

            return new Response('test');
        });

        $app = $this->sessionify($app, $config);

        $client = new Client($app);
        $client->setServerParameters(array('REQUEST_TIME' => static::SIMULATED_TIME));
        $client->request('GET', '/');

        $this->assertEquals('test', $client->getResponse()->getContent());

        $cookies = $client->getResponse()->headers->getCookies();
        $this->assertCount(1, $cookies);

        $cookie = $cookies[0];

        $expectedCookie = new Cookie(
            $this->mockFileSessionStorage->getName(),
            $this->mockFileSessionStorage->getId(),
            $expectedExpire,
            $expectedPath,
            $expectedDomain,
            $expectedSecure,
            $expectedHttponly
        );

        $this->assertEquals($expectedCookie, $cookie);

        $bag = $this->mockFileSessionStorage->getBag('attributes');
        $this->assertEquals('is set', $bag->get('some_session_var'));
    }

    public function provideOverrideSessionParams()
    {
        return [
            [
                'example.com', '/test-path', true, true, static::SIMULATED_TIME + 300,
                [
                    'session.cookie_params' => [
                        'lifetime' => 300,
                        'domain' => 'example.com',
                        'path' => '/test-path',
                        'secure' => true,
                        'httponly' => true,
                    ]
                ]
            ],
            [
                'example.com', '/', false, false, 0,
                [
                    'session.cookie_params' => [
                        'domain' => 'example.com',
                    ]
                ]
            ],
            [
                '', '/test-path', false, false, 0,
                [
                    'session.cookie_params' => [
                        'path' => '/test-path',
                    ]
                ]
            ],
            [
                '', '/', true, false, 0,
                [
                    'session.cookie_params' => [
                        'secure' => true,
                    ]
                ]
            ],
            [
                '', '/', false, true, 0,
                [
                    'session.cookie_params' => [
                        'httponly' => true,
                    ]
                ]
            ],
            [
                '', '/', false, false, static::SIMULATED_TIME + 300,
                [
                    'session.cookie_params' => [
                        'lifetime' => 300,
                    ]
                ]
            ],
            [
                '', '/', false, false, static::SIMULATED_TIME - 300,
                [
                    'session.cookie_params' => [
                        'lifetime' => -300,
                    ]
                ]
            ],
        ];
    }
}
