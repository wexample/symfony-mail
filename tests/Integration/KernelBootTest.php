<?php

namespace Wexample\SymfonyMail\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Wexample\SymfonyMail\WexampleSymfonyMailBundle;

class KernelBootTest extends KernelTestCase
{
    public function testTheBundleBootsInTheFixtureKernel(): void
    {
        $kernel = self::bootKernel();

        $this->assertInstanceOf(
            WexampleSymfonyMailBundle::class,
            $kernel->getBundle('WexampleSymfonyMailBundle')
        );
    }
}
