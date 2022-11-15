<?php

namespace Frosh\DevelopmentHelper\Component\Twig\NodeVisitor;

use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\BlockNode;
use Twig\Node\BodyNode;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\TextNode;
use Twig\NodeVisitor\AbstractNodeVisitor;
use Twig\Source;

class BlogCommentNodeVisitor extends AbstractNodeVisitor
{
    /**
     * @var string
     */
    private $kernelRootDir;

    private array $twigExcludeKeywords;

    public function __construct(string $kernelRootDir, array $twigExcludeKeywords)
    {
        $this->kernelRootDir = $kernelRootDir;
        $this->twigExcludeKeywords = $twigExcludeKeywords;
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

        if ($this->shouldSkip($node)) {
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

    private function shouldSkip(Node $node): bool
    {
        $name = $node->getTemplateName();

        if ($node instanceof BlockNode) {
            $name = $node->getAttribute('name');
        }

        if (empty($name)) {
            return true;
        }

        foreach ($this->twigExcludeKeywords as $key) {
            if (strpos($name, $key) !== false) {
                return true;
            }
        }

        return false;
    }
}
