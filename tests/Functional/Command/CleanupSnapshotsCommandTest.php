<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\PageBundle\Tests\Functional\Command;

use Doctrine\ORM\EntityManagerInterface;
use Sonata\PageBundle\Tests\App\Entity\SonataPagePage;
use Sonata\PageBundle\Tests\App\Entity\SonataPageSite;
use Sonata\PageBundle\Tests\App\Entity\SonataPageSnapshot;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CleanupSnapshotsCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        static::bootKernel();

        $this->commandTester = new CommandTester(
            (new Application(static::createKernel()))->find('sonata:page:cleanup-snapshots')
        );
    }

    public function testThrowExceptionOnInvalidValue(): void
    {
        static::expectException(\InvalidArgumentException::class);
        static::expectExceptionMessage('Please provide an integer value for the option "keep-snapshots".');

        $this->commandTester->execute(['--keep-snapshots' => 'invalid']);
    }

    /**
     * @dataProvider provideKeepSnapshotsCases
     *
     * @param array{'--site'?: array<int>, '--keep-snapshots'?: int} $input
     */
    public function testDoCleanups(array $input, int $snapshotCount): void
    {
        $this->prepareData();

        static::assertSame(2, $this->countSnapshots());

        $this->commandTester->execute($input);

        static::assertSame($snapshotCount, $this->countSnapshots());

        static::assertStringContainsString('done!', $this->commandTester->getDisplay());
    }

    /**
     * @return iterable<array<string|array<string, mixed>>>
     *
     * @phpstan-return iterable<array{0: array{'--site'?: array<int>, '--keep-snapshots'?: int}, 1: int}>
     */
    public static function provideKeepSnapshotsCases(): iterable
    {
        yield 'Keep no snapshots' => [[
            '--keep-snapshots' => 0,
        ], 0];

        yield 'Keep one snapshot' => [[
            '--keep-snapshots' => 1,
        ], 2];

        yield 'Only one site' => [[
            '--site' => [1],
            '--keep-snapshots' => 0,
        ], 1];
    }

    private function prepareData(): void
    {
        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($manager instanceof EntityManagerInterface);

        $site = new SonataPageSite();
        $site->setName('name');
        $site->setHost('localhost');

        $site2 = new SonataPageSite();
        $site2->setName('another_site');
        $site2->setHost('sonata-project.org');

        $page = new SonataPagePage();
        $page->setName('name');
        $page->setTemplateCode('default');
        $page->setSite($site);

        $page2 = new SonataPagePage();
        $page2->setName('another_page');
        $page2->setTemplateCode('default');
        $page2->setSite($site2);

        $snapshot = new SonataPageSnapshot();
        $snapshot->setName('name');
        $snapshot->setRouteName('sonata_page_test_route');
        $snapshot->setPage($page);

        $snapshot2 = new SonataPageSnapshot();
        $snapshot2->setName('another_snapshot');
        $snapshot2->setRouteName('sonata_page_test_route');
        $snapshot2->setPage($page2);

        $manager->persist($site);
        $manager->persist($site2);
        $manager->persist($page);
        $manager->persist($page2);
        $manager->persist($snapshot);
        $manager->persist($snapshot2);

        $manager->flush();
    }

    private function countSnapshots(): int
    {
        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($manager instanceof EntityManagerInterface);

        return $manager->getRepository(SonataPageSnapshot::class)->count([]);
    }
}
