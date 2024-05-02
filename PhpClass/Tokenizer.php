<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\PhpClass;

use Magento\Framework\Filesystem\File\ReadFactory;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

class Tokenizer
{
    private ReadFactory $readFactory;

    /**
     * @param ReadFactory $readFactory
     */
    public function __construct(
        ReadFactory $readFactory
    ) {
        $this->readFactory = $readFactory;
    }

    /**
     * @return string[]
     */
    public function getStringTokensFromFilename(string $filename): array
    {
        $source = $this->getSourceFromFilename($filename);
        if (empty($source)) {
            return [];
        }

        $tokens = token_get_all($source);

        $functions = [];
        foreach ($tokens as $token) {
            if (is_array($token) && $token[0] === T_STRING) {
                $functions[] = $token[1];
            }
        }

        return $functions;
    }

    /**
     * @param string $filename
     * @return array
     */
    public function getImportedClassnamesFromFile(string $filename): array
    {
        $source = $this->getSourceFromFilename($filename);
        if (empty($source)) {
            return [];
        }

        return $this->getImportedClassnamesFromSource($source);
    }

    /**
     * @param string $source
     * @return array
     */
    public function getImportedClassnamesFromSource(string $source): array
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $traverser = new NodeTraverser;
        $traverser->addVisitor(new NameResolver);
        $stmts = $parser->parse($source);
        $stmts = $traverser->traverse($stmts);

        $usedClassNames = [];

        $nodeFinder = new NodeFinder();
        $nodes = $nodeFinder->findInstanceOf($stmts, FullyQualified::class);
        foreach ($nodes as $node) {
            // ignore function usages, we only care about classes
            if (function_exists($node->toString())) {
                continue;
            }
            $usedClassNames[] = $node->toString();
        }

        return array_unique($usedClassNames);
    }

    /**
     * @param string $filename
     * @return array|string
     */
    private function getSourceFromFilename(string $filename)
    {
        if (empty($filename)) {
            return '';
        }

        $read = $this->readFactory->create($filename, 'file');
        return $read->readAll();
    }
}
