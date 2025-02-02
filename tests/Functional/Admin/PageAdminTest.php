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

namespace Sonata\PageBundle\Tests\Functional\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Tests\App\Entity\SonataPageBlock;
use Sonata\PageBundle\Tests\App\Entity\SonataPagePage;
use Sonata\PageBundle\Tests\App\Entity\SonataPageSite;
use Sonata\PageBundle\Tests\App\Entity\SonataPageSnapshot;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PageAdminTest extends WebTestCase
{
    /**
     * @dataProvider provideCrudUrlsCases
     *
     * @param array<string, mixed> $parameters
     */
    public function testCrudUrls(string $url, array $parameters = []): void
    {
        $client = self::createClient();

        $this->prepareData();

        $client->request('GET', $url, $parameters);

        self::assertResponseIsSuccessful();
    }

    /**
     * @return iterable<array<string|array<string, mixed>>>
     *
     * @phpstan-return iterable<array{0: string, 1?: array<string, mixed>}>
     */
    public static function provideCrudUrlsCases(): iterable
    {
        yield 'Tree Page' => ['/admin/tests/app/sonatapagepage/tree'];

        yield 'List Page' => ['/admin/tests/app/sonatapagepage/list', ['filter' => [
            'name' => ['value' => 'name'],
        ]]];

        yield 'List Sites' => ['/admin/tests/app/sonatapagepage/create'];
        yield 'Create Page' => ['/admin/tests/app/sonatapagepage/create', ['siteId' => 1]];
        yield 'Edit Page' => ['/admin/tests/app/sonatapagepage/1/edit'];
        yield 'Show Page' => ['/admin/tests/app/sonatapagepage/1/show'];
        yield 'Remove Page' => ['/admin/tests/app/sonatapagepage/1/delete'];
        yield 'Compose Page' => ['/admin/tests/app/sonatapagepage/1/compose'];
        yield 'Compose Container Page' => ['/admin/tests/app/sonatapagepage/compose/container/1'];

        // Snapshot child pages
        yield 'List Snapshot Page' => ['/admin/tests/app/sonatapagepage/1/sonatapagesnapshot/list'];
        yield 'Create Snapshot Page' => ['/admin/tests/app/sonatapagepage/1/sonatapagesnapshot/create'];
        yield 'Edit Snapshot Page' => ['/admin/tests/app/sonatapagepage/1/sonatapagesnapshot/1/edit'];
        yield 'Remove Snapshot Page' => ['/admin/tests/app/sonatapagepage/1/sonatapagesnapshot/1/delete'];

        // Block child pages
        yield 'List Block' => ['/admin/tests/app/sonatapagepage/1/sonatapageblock/list'];
        yield 'List Block types' => ['/admin/tests/app/sonatapagepage/1/sonatapageblock/create'];

        yield 'Create Block' => ['/admin/tests/app/sonatapagepage/1/sonatapageblock/create', [
            'type' => 'sonata.page.block.shared_block',
        ]];

        yield 'Edit Block' => ['/admin/tests/app/sonatapagepage/1/sonatapageblock/1/edit'];
        yield 'Remove Block' => ['/admin/tests/app/sonatapagepage/1/sonatapageblock/1/delete'];
        yield 'Compose preview Block' => ['/admin/tests/app/sonatapagepage/1/sonatapageblock/2/compose-preview'];
    }

    public function testRedirectWithSiteSelectedWhenThereIsOnlyOneSite(): void
    {
        $client = self::createClient();

        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($manager instanceof EntityManagerInterface);

        $site = new SonataPageSite();
        $site->setName('name');
        $site->setHost('localhost');

        $manager->persist($site);
        $manager->flush();

        $client->request('GET', '/admin/tests/app/sonatapagepage/create', [
            'uniqid' => 'page',
        ]);

        self::assertResponseRedirects('/admin/tests/app/sonatapagepage/create?siteId=1&uniqid=page');
    }

    /**
     * @dataProvider provideFormUrlsCases
     *
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $fieldValues
     */
    public function testFormsUrls(string $url, array $parameters, string $button, array $fieldValues = []): void
    {
        $client = self::createClient();

        $this->prepareData();

        $client->request('GET', $url, $parameters);
        $client->submitForm($button, $fieldValues);
        $client->followRedirect();

        self::assertResponseIsSuccessful();
    }

    /**
     * @return iterable<array<string|array<string, mixed>>>
     *
     * @phpstan-return iterable<array{0: string, 1: array<string, mixed>, 2: string, 3?: array<string, mixed>}>
     */
    public static function provideFormUrlsCases(): iterable
    {
        yield 'Create Page' => ['/admin/tests/app/sonatapagepage/create', [
            'uniqid' => 'page',
            'siteId' => 1,
        ], 'btn_create_and_list', [
            'page[site]' => 1,
            'page[name]' => 'Name',
            'page[enabled]' => 1,
            'page[position]' => 1,
            'page[type]' => 'sonata.page.service.default',
            'page[templateCode]' => 'default',
            'page[parent]' => 1,
            'page[pageAlias]' => 'alias',
            'page[slug]' => 'name',
            'page[customUrl]' => '/custom-url',
            'page[title]' => 'Title',
            'page[metaKeyword]' => 'name, name2',
            'page[metaDescription]' => 'Name is a good name.',
            'page[javascript]' => 'alert(\'Hello World\');',
            'page[stylesheet]' => 'position: absolute;',
            'page[rawHeaders]' => 'X-powered-by: PHP',
        ]];

        yield 'Create Page With Url' => ['/admin/tests/app/sonatapagepage/create', [
            'uniqid' => 'page',
            'siteId' => 1,
            'url' => '/some/random/url',
        ], 'btn_create_and_list', []];

        yield 'Edit Page' => ['/admin/tests/app/sonatapagepage/1/edit', [], 'btn_update_and_list', []];
        yield 'Remove Page' => ['/admin/tests/app/sonatapagepage/1/delete', [], 'btn_delete'];

        // Snapshot child pages
        yield 'Create Snapshot Page' => ['/admin/tests/app/sonatapagepage/1/sonatapagesnapshot/create', [], 'btn_create_and_list', []];
        yield 'Edit Snapshot Page' => ['/admin/tests/app/sonatapagesnapshot/1/edit', [], 'btn_update_and_list', []];
        yield 'Remove Snapshot Page' => ['/admin/tests/app/sonatapagesnapshot/1/delete', [], 'btn_delete'];

        // Block child pages
        yield 'Create Block Page - Children Pages' => ['/admin/tests/app/sonatapagepage/1/sonatapageblock/create', [
            'uniqid' => 'block',
            'type' => 'sonata.page.block.children_pages',
        ], 'btn_create_and_edit', [
            'block[name]' => 'Name',
            'block[enabled]' => 1,
            'block[settings][title]' => 'Title',
            'block[settings][translation_domain]' => 'SonataPageBundle',
            'block[settings][icon]' => 'fa fa-home',
            'block[settings][current]' => 1,
            'block[settings][pageId]' => 1,
            'block[settings][class]' => 'custom_class',
        ]];

        yield 'Edit Block Page' => ['/admin/tests/app/sonatapageblock/1/edit', [], 'btn_update_and_list', []];
        yield 'Remove Block Page' => ['/admin/tests/app/sonatapageblock/1/delete', [], 'btn_delete'];
    }

    /**
     * @dataProvider provideBatchActionsCases
     */
    public function testBatchActions(string $action): void
    {
        $client = self::createClient();

        $this->prepareData();

        $client->request('GET', '/admin/tests/app/sonatapagepage/list', ['filter' => [
            'name' => ['value' => 'name'],
        ]]);
        $client->submitForm('OK', [
            'all_elements' => true,
            'action' => $action,
        ]);
        $client->submitForm('Yes, execute');
        $client->followRedirect();

        self::assertResponseIsSuccessful();
    }

    /**
     * @return iterable<array<string>>
     *
     * @phpstan-return iterable<array{0: string}>
     */
    public static function provideBatchActionsCases(): iterable
    {
        yield 'Delete Pages' => ['delete'];
        yield 'Create Snaphosts' => ['snapshot'];
    }

    /**
     * @dataProvider provideTabMenuCases
     */
    public function testTabMenu(string $link, string $location): void
    {
        $client = self::createClient();

        $this->prepareData();

        $client->request('GET', '/admin/tests/app/sonatapagepage/1/edit');
        $crawler = $client->getCrawler();

        $link = $crawler->selectLink($link)->link();
        $client->click($link);

        static::assertSame($client->getHistory()->current()->getUri(), 'http://localhost'.$location);
        self::assertResponseIsSuccessful();
    }

    /**
     * @return iterable<array<string>>
     *
     * @phpstan-return iterable<array{0: string, 1: string}>
     */
    public static function provideTabMenuCases(): iterable
    {
        yield 'Edit page link' => ['Edit page', '/admin/tests/app/sonatapagepage/1/edit'];
        yield 'Composer link' => ['Composer', '/admin/tests/app/sonatapagepage/1/compose'];
        yield 'Block list link' => ['Block List', '/admin/tests/app/sonatapagepage/1/sonatapageblock/list'];
        yield 'Publications link' => ['Publications', '/admin/tests/app/sonatapagepage/1/sonatapagesnapshot/list'];
        yield 'View page link' => ['View page', '/'];
    }

    private function prepareData(): void
    {
        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($manager instanceof EntityManagerInterface);

        $site = new SonataPageSite();
        $site->setRelativePath('/');
        $site->setName('name');
        $site->setHost('localhost');
        $site->setEnabled(true);

        $site2 = new SonataPageSite();
        $site2->setName('name');
        $site2->setHost('localhost');

        $page = new SonataPagePage();
        $page->setName('name');
        $page->setUrl('/');
        $page->setTemplateCode('default');
        $page->setEnabled(true);
        $page->setSite($site);

        $snapshot = new SonataPageSnapshot();
        $snapshot->setName('name');
        $snapshot->setUrl('/');
        $snapshot->setRouteName(PageInterface::PAGE_ROUTE_CMS_NAME);
        $snapshot->setEnabled(true);
        $snapshot->setPublicationDateStart(new \DateTimeImmutable());
        $snapshot->setPage($page);
        $snapshot->setSite($site);

        $parentBlock = new SonataPageBlock();
        $parentBlock->setType('sonata.page.block.container');
        $parentBlock->setSetting('code', 'content');

        $block = new SonataPageBlock();
        $block->setType('sonata.block.service.text');

        $block2 = new SonataPageBlock();
        $block2->setType('non_existent_block');

        $page->addBlock($parentBlock);
        $parentBlock->addChild($block);
        $parentBlock->addChild($block2);

        $manager->persist($site);
        $manager->persist($site2);
        $manager->persist($page);
        $manager->persist($snapshot);

        $manager->flush();
    }
}
