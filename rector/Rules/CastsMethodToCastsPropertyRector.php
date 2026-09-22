<?php

declare(strict_types=1);

namespace Rector\Rules;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class CastsMethodToCastsPropertyRector extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [
            ClassMethod::class,
        ];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $node instanceof ClassMethod) {
            return null;
        }

        if ($node->name->toString() !== 'casts') {
            return null;
        }

        if (! $node->stmts) {
            return null;
        }

        if (count($node->stmts) !== 1) {
            return null;
        }

        $return = $node->stmts[0];

        if (! $return instanceof Node\Stmt\Return_) {
            return null;
        }

        if (! $return->expr instanceof Array_) {
            return null;
        }

        $array = $return->expr;

        return new Property(
            Modifiers::PROTECTED,
            [
                new PropertyItem(
                    'casts',
                    $array,
                ),
            ],
        );
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert Eloquent casts() method to $casts property',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
                    protected function casts(): array
                    {
                        return [
                            'age' => 'integer',
                        ];
                    }
                    CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
                    protected $casts = [
                        'age' => 'integer',
                    ];
                    CODE_SAMPLE,
                ),
            ],
        );
    }
}
