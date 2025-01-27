<?php

namespace Frosh\DevelopmentHelper\Component\Twig\NodeVisitor;

use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\FilesystemLoader;
use Twig\Node\BlockNode;
use Twig\Node\BodyNode;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\TextNode;
use Twig\NodeVisitor\AbstractNodeVisitor;
use Twig\Source;

class BlogCommentNodeVisitor extends AbstractNodeVisitor
{
    private const BLACKLIST_KEYS = [
        'head_meta_tags',
        'layout_head_title',
        'page_product_detail_buy_form_action',
        'base_body_classes',
        'page_checkout_additional',
        'buy_widget_buy_form_action',
        'sitemap',
        'sitemap_content',
        'sitemap_content_sitemaps',
    ];

    /**
     * @var string
     */
    private $kernelRootDir;

    public function __construct(string $kernelRootDir)
    {
        $this->kernelRootDir = $kernelRootDir;
    }


    /**
     * @inheritDoc
     */
    protected function doEnterNode(Node $node, Environment $env): Node
    {
        return $node;
    }

    /**
     * @inheritDoc
     */
    protected function doLeaveNode(Node $node, Environment $env): Node
    {
        $twigSource = $node->getSourceContext();

        if ($env->getLoader() instanceof ArrayLoader) {
            return $node;
        }

        if ($twigSource === null) {
            return $node;
        }

        if ($twigSource->getPath() === '') {
            return $node;
        }

        $path = $node->getTemplateName();
        if ($node->getSourceContext() instanceof Source) {
            $path = ltrim(str_replace($this->kernelRootDir, '', $node->getSourceContext()->getPath()), '/');
        }

        if ($this->isBlacklisted($node)) {
            return $node;
        }

        if ($node instanceof ModuleNode) {
            $node->setNode('body', new BodyNode([
                new TextNode('<!-- INCLUDE BEGIN ' . $node->getTemplateName() . ' (' . $path .') -->', 0),
                $node->getNode('body'),
                new TextNode('<!-- INCLUDE END ' . $node->getTemplateName() . ' -->', 0),
            ]));
        } elseif ($node instanceof BlockNode) {
            $name = $node->getAttribute('name');
            $node->setNode('body', new BodyNode([
                new TextNode('<!-- BLOCK BEGIN ' . $name . ' (' . $path .') -->', 0),
                $node->getNode('body'),
                new TextNode('<!-- BLOCK END ' . $name . ' -->', 0),
            ]));
        }

        return $node;
    }

    public function getPriority(): int
    {
        return 0;
    }

    private function isBlacklisted(Node $node): bool
    {
        $name = $node->getTemplateName();

        if ($node instanceof BlockNode) {
            $name = $node->getAttribute('name');
        }

        if (empty($name)) {
            return true;
        }

        foreach (self::BLACKLIST_KEYS as $key) {
            if (strpos($name, $key) !== false) {
                return true;
            }
        }

        return false;
    }
}
