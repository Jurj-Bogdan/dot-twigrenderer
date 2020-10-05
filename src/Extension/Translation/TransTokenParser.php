<?php

namespace Dot\Twig\Extension\Translation;

use Twig\Error\SyntaxError;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\Node\TextNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class TransTokenParser
 * @package Dot\Twig\Extension\Translation
 */
class TransTokenParser extends AbstractTokenParser
{
    /**
     * @param Token $token
     * @return TransNode|Node
     * @throws SyntaxError
     */
    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $count = null;
        $plural = null;
        $notes = null;

        if (!$stream->test(Token::BLOCK_END_TYPE)) {
            $body = $this->parser->getExpressionParser()->parseExpression();
        } else {
            $stream->expect(Token::BLOCK_END_TYPE);
            $body = $this->parser->subparse([$this, 'decideForFork']);
            $next = $stream->next()->getValue();

            if ('plural' === $next) {
                $count = $this->parser->getExpressionParser()->parseExpression();
                $stream->expect(Token::BLOCK_END_TYPE);
                $plural = $this->parser->subparse([$this, 'decideForFork']);

                if ('notes' === $stream->next()->getValue()) {
                    $stream->expect(Token::BLOCK_END_TYPE);
                    $notes = $this->parser->subparse([$this, 'decideForEnd'], true);
                }
            } elseif ('notes' === $next) {
                $stream->expect(Token::BLOCK_END_TYPE);
                $notes = $this->parser->subparse([$this, 'decideForEnd'], true);
            }
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        $this->checkTransString($body, $lineno);

        return new TransNode($body, $plural, $count, $notes, $lineno, $this->getTag());
    }

    /**
     * @param Token $token
     * @return bool
     */
    public function decideForFork(Token $token)
    {
        return $token->test(['plural', 'notes', 'endtrans']);
    }

    /**
     * @param Token $token
     * @return bool
     */
    public function decideForEnd(Token $token)
    {
        return $token->test('endtrans');
    }

    /**
     * @return string
     */
    public function getTag()
    {
        return 'trans';
    }

    /**
     * @param Node $body
     * @param $lineno
     * @throws SyntaxError
     */
    private function checkTransString(Node $body, $lineno)
    {
        foreach ($body as $i => $node) {
            if (
                $node instanceof TextNode
                ||
                ($node instanceof PrintNode && $node->getNode('expr') instanceof NameExpression)
            ) {
                continue;
            }

            throw new SyntaxError(
                sprintf('The text to be translated with "trans" can only contain references to simple variables'),
                $lineno
            );
        }
    }
}
