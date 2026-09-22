<?php

declare(strict_types=1);

namespace Rector\Rules;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Naming\Import\ImportNames;
use Rector\Rector\AbstractRector;
use Rector\RuleDefinition\RuleDefinition;

final class ConvertScopeMethodsToAttributesRector extends AbstractRector
{
    public function __construct(
        private readonly ImportNames $importNames,
    ) {}

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

        $methodName = $node->name->toString();

        if (! str_starts_with($methodName, 'scope')) {
            return null;
        }

        $newMethodName = lcfirst(mb_substr($methodName, 5));

        if ($newMethodName === '') {
            return null;
        }

        // Already converted
        foreach ($node->attrGroups as $attributeGroup) {
            foreach ($attributeGroup->attrs as $attribute) {
                if ($attribute->name->toString() === 'Scope') {
                    return null;
                }
            }
        }

        // scopeFilter() -> filter()
        $node->name = new Identifier($newMethodName);

        // public/protected -> protected
        $node->flags &= ~ClassMethod::MODIFIER_PUBLIC;
        $node->flags |= ClassMethod::MODIFIER_PROTECTED;

        $node->attrGroups[] = new AttributeGroup([
            new Attribute(
                new Name('Scope')
            ),
        ]);

        $this->importNames->importNames(
            $node,
            [
                new Name('Illuminate\Database\Eloquent\Attributes\Scope'),
            ]
        );

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert Eloquent scope methods to Laravel #[Scope] attributes',
            []
        );
    }
}
