<?php

namespace App\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

/**
 * "REPLACE" "(" StringPrimary "," StringSecondary "," StringThird ")".
 *
 * @see    www.prohoney.com
 * @since   2.0
 *
 * @author  Igor Aleksejev
 */
class ReplaceFunction extends FunctionNode
{
    /**
     * @var \Doctrine\ORM\Query\AST\Node
     */
    public $stringPrimary;
    /**
     * @var \Doctrine\ORM\Query\AST\Node
     */
    public $stringSecondary;
    /**
     * @var \Doctrine\ORM\Query\AST\Node
     */
    public $stringThird;

    /**
     * @override
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'REPLACE('.
            $this->stringPrimary->dispatch($sqlWalker).', '.
            $this->stringSecondary->dispatch($sqlWalker).', '.
            $this->stringThird->dispatch($sqlWalker).
        ')';
        /*        return $sqlWalker->getConnection()->getDatabasePlatform()->getReplaceExpression(
                                $this->stringPrimary, $this->stringSecondary, $this->stringThird
                );*/
    }

    /**
     * @override
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->stringPrimary = $parser->StringPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->stringSecondary = $parser->StringPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->stringThird = $parser->StringPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
