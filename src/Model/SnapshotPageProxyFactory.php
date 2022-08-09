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

namespace Sonata\PageBundle\Model;

final class SnapshotPageProxyFactory implements SnapshotPageProxyFactoryInterface
{
    /**
     * @var class-string<SnapshotPageProxyInterface>
     */
    private string $snapshotPageProxyClass;

    /**
     * @param class-string<SnapshotPageProxyInterface> $snapshotPageProxyClass
     */
    public function __construct(string $snapshotPageProxyClass)
    {
        $this->snapshotPageProxyClass = $snapshotPageProxyClass;
    }

    public function create(
        SnapshotManagerInterface $manager,
        TransformerInterface $transformer,
        SnapshotInterface $snapshot
    ): SnapshotPageProxyInterface {
        return new $this->snapshotPageProxyClass($manager, $transformer, $snapshot);
    }
}
