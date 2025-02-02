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

namespace Sonata\PageBundle\Block;

use Sonata\AdminBundle\Form\FormMapper as AdminFormMapper;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\AbstractBlockService;
use Sonata\BlockBundle\Block\Service\EditableBlockService;
use Sonata\BlockBundle\Form\Mapper\FormMapper;
use Sonata\BlockBundle\Form\Type\ContainerTemplateType;
use Sonata\BlockBundle\Meta\Metadata;
use Sonata\BlockBundle\Meta\MetadataInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\Form\Type\CollectionType;
use Sonata\Form\Type\ImmutableArrayType;
use Sonata\Form\Validator\ErrorElement;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
final class ContainerBlockService extends AbstractBlockService implements EditableBlockService
{
    public function configureCreateForm(FormMapper $form, BlockInterface $block): void
    {
        $this->configureEditForm($form, $block);
    }

    public function configureEditForm(FormMapper $form, BlockInterface $block): void
    {
        if (!$form instanceof AdminFormMapper) {
            throw new \InvalidArgumentException('This block can only be edited through SonataAdmin');
        }

        $form->add('settings', ImmutableArrayType::class, [
            'keys' => [
                ['code', TextType::class, [
                    'required' => false,
                    'label' => 'form.label_code',
                    'translation_domain' => 'SonataPageBundle',
                ]],
                ['layout', TextareaType::class, [
                    'label' => 'form.label_layout',
                    'translation_domain' => 'SonataPageBundle',
                ]],
                ['class', TextType::class, [
                    'required' => false,
                    'label' => 'form.label_class',
                    'translation_domain' => 'SonataPageBundle',
                ]],
                ['template', ContainerTemplateType::class, [
                    'label' => 'form.label_template_code',
                    'translation_domain' => 'SonataPageBundle',
                ]],
            ],
            'translation_domain' => 'SonataPageBundle',
        ]);

        $form->add('children', CollectionType::class, [], [
            'edit' => 'inline',
            'inline' => 'table',
            'sortable' => 'position',
        ]);
    }

    public function execute(BlockContextInterface $blockContext, ?Response $response = null): Response
    {
        $template = $blockContext->getTemplate();
        \assert(null !== $template);

        return $this->renderResponse($template, [
            'block' => $blockContext->getBlock(),
            'decorator' => $this->getDecorator($blockContext->getSetting('layout')),
            'settings' => $blockContext->getSettings(),
        ], $response);
    }

    public function validate(ErrorElement $errorElement, BlockInterface $block): void
    {
    }

    public function configureSettings(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'code' => '',
            'layout' => '{{ CONTENT }}',
            'class' => '',
            'template' => '@SonataPage/Block/block_container.html.twig',
        ]);
    }

    public function getMetadata(): MetadataInterface
    {
        return new Metadata('sonata.page.block.container', null, null, 'SonataPageBundle', [
            'class' => 'fa fa-square-o',
        ]);
    }

    /**
     * Returns a decorator object/array from the container layout setting.
     *
     * @return array{pre?: string, post?: string}
     */
    private function getDecorator(string $layout): array
    {
        $key = '{{ CONTENT }}';
        if (!str_contains($layout, $key)) {
            return [];
        }

        $segments = explode($key, $layout);
        $decorator = [
            'pre' => $segments[0] ?? '',
            'post' => $segments[1] ?? '',
        ];

        return $decorator;
    }
}
