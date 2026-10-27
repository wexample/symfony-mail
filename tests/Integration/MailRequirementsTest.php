<?php

namespace Wexample\SymfonyMail\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyLoader\Class\Requirement;
use Wexample\SymfonyLoader\Service\RequirementsService;
use Wexample\SymfonyMail\DependencyInjection\WexampleSymfonyMailExtension;
use Wexample\SymfonyMail\Tests\Fixtures\App\AppKernel;

/**
 * The sender of every mail is a requirement symfony-loader checks: given by
 * the fixture, missing from a configuration without one, and the variable it
 * names when it is read from the environment.
 */
class MailRequirementsTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return AppKernel::class;
    }

    public function testTheFixtureNamesItsSender(): void
    {
        static::bootKernel();

        $requirements = static::getContainer()->get(RequirementsService::class)->getRequirements();
        $names = array_map(static fn (Requirement $requirement) => $requirement->name, $requirements);

        $this->assertContains('framework.mailer.headers.From', $names);
        $this->assertSame([], static::getContainer()->get(RequirementsService::class)->findMissing());
    }

    public function testAConfigurationWithoutSenderMissesIt(): void
    {
        $container = $this->prepend(['mailer' => ['dsn' => 'null://null']]);

        $this->assertFalse($container->getParameter(WexampleSymfonyMailExtension::PARAMETER_SENDER));
        $this->assertNull($container->getParameter(WexampleSymfonyMailExtension::PARAMETER_SENDER_ENV));
    }

    public function testASenderReadFromTheEnvironmentNamesItsVariable(): void
    {
        $container = $this->prepend(['mailer' => ['headers' => ['From' => '%env(MAILER_FROM)%']]]);

        $this->assertTrue($container->getParameter(WexampleSymfonyMailExtension::PARAMETER_SENDER));
        $this->assertSame('MAILER_FROM', $container->getParameter(WexampleSymfonyMailExtension::PARAMETER_SENDER_ENV));
    }

    private function prepend(array $framework): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', []);
        $container->setParameter('kernel.project_dir', sys_get_temp_dir());
        $container->prependExtensionConfig('framework', $framework);
        (new WexampleSymfonyMailExtension())->prepend($container);

        return $container;
    }
}
