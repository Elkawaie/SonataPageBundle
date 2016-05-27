<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\PageBundle\Tests\Block;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mock Templating class.
 */
class FakeTemplating implements EngineInterface
{
    public $view;
    public $parameters;
    public $response;
    public $name;

    /**
     * {@inheritdoc}
     */
    public function render($name, array $parameters = array())
    {
        $this->name = $name;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function renderResponse($view, array $parameters = array(), Response $response = null)
    {
        $this->view = $view;
        $this->parameters = $parameters;

        if ($response) {
            return $response;
        }

        return new Response();
    }

    /**
     * {@inheritdoc}
     */
    public function supports($name)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function exists($name)
    {
        return true;
    }
}
