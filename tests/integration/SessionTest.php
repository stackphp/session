<?php

namespace integration;

use common\AbstractTestCase;
use Pimple;
use Stack\CallableHttpKernel;
use Stack\Session;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SessionTest extends AbstractTestCase
{
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
    public function testOverrideSessionParams($expectedDomain, $expectedPath, $expectedSecure, $expectedHttponly, $expectedLifetime, $config)
    {
        $serverRequestTime = null;
        $app = new CallableHttpKernel(function (Request $request) use (&$serverRequestTime) {
            $serverRequestTime = $request->server->get('REQUEST_TIME');
            $request->getSession()->set('some_session_var', 'is set');

            return new Response('test');
        });

        $app = $this->sessionify($app, $config);

        $client = new Client($app);

        $client->request('GET', '/');

        $this->assertEquals('test', $client->getResponse()->getContent());

        $cookies = $client->getResponse()->headers->getCookies();
        $this->assertCount(1, $cookies);

        $cookie = $cookies[0];

        if (0 === $cookie->getExpiresTime()) {
            // Special case for a Cookie with a 0 (zero) expires time, we want
            // to just compare directly against the expected lifetime with no
            // time calculation.
            $expectedExpire = $expectedLifetime;
        } else {
            // In all other cases, we want to subtract the server request time
            // from the expires time to see if it matches our expected lifetime.
            $expectedExpire = $serverRequestTime + $expectedLifetime;
        }

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
            ['example.com', '/test-path', true, true, 300, ['session.cookie_params' => [
                'lifetime' => 300,
                'domain' => 'example.com',
                'path' => '/test-path',
                'secure' => true,
                'httponly' => true,
            ]]],
            ['example.com', '/', false, false, 0, ['session.cookie_params' => [
                'domain' => 'example.com',
            ]]],
            ['', '/test-path', false, false, 0, ['session.cookie_params' => [
                'path' => '/test-path',
            ]]],
            ['', '/', true, false, 0, ['session.cookie_params' => [
                'secure' => true,
            ]]],
            ['', '/', false, true, 0, ['session.cookie_params' => [
                'httponly' => true,
            ]]],
            ['', '/', false, false, 300, ['session.cookie_params' => [
                'lifetime' => 300,
            ]]],
            ['', '/', false, false, -300, ['session.cookie_params' => [
                'lifetime' => -300,
            ]]],
        ];
    }
}
