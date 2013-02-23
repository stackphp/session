<?php

namespace Stack;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

/** @covers Stack\Session */
class StackTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function withoutMiddlewaresItShouldReturnOriginalResponse()
    {
    }
}
