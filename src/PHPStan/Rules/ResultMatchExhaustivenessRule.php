<?php

declare(strict_types=1);

namespace Jsoizo\Result\PHPStan\Rules;

use Jsoizo\Result\Failure;
use Jsoizo\Result\Result;
use Jsoizo\Result\Success;
use PhpParser\Node;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * Ensures match expressions on Result types are exhaustive.
 *
 * @implements Rule<Match_>
 */
final class ResultMatchExhaustivenessRule implements Rule
{
    public function getNodeType(): string
    {
        return Match_::class;
    }

    /**
     * @param Match_ $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $resultVariables = $this->findResultVariables($node, $scope);

        if ($resultVariables === []) {
            return [];
        }

        $errors = [];
        foreach ($resultVariables as $varName) {
            $error = $this->checkExhaustiveness($node, $varName);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function findResultVariables(Match_ $node, Scope $scope): array
    {
        $resultVars = [];
        $resultType = new ObjectType(Result::class);

        foreach ($node->arms as $arm) {
            if ($arm->conds === null) {
                continue;
            }

            foreach ($arm->conds as $cond) {
                if (!$cond instanceof Instanceof_) {
                    continue;
                }

                if (!$cond->class instanceof Name) {
                    continue;
                }

                $className = $scope->resolveName($cond->class);
                if ($className !== Success::class && $className !== Failure::class) {
                    continue;
                }

                $varType = $scope->getType($cond->expr);
                if (!$resultType->isSuperTypeOf($varType)->yes()) {
                    continue;
                }

                $varName = $this->getExpressionKey($cond->expr);
                if ($varName !== null) {
                    $resultVars[$varName] = true;
                }
            }
        }

        return array_keys($resultVars);
    }

    private function checkExhaustiveness(Match_ $node, string $varName): ?IdentifierRuleError
    {
        $hasSuccess = false;
        $hasFailure = false;
        $hasDefault = false;

        foreach ($node->arms as $arm) {
            if ($arm->conds === null) {
                $hasDefault = true;
                continue;
            }

            foreach ($arm->conds as $cond) {
                if (!$cond instanceof Instanceof_) {
                    continue;
                }

                if ($this->getExpressionKey($cond->expr) !== $varName) {
                    continue;
                }

                if (!$cond->class instanceof Name) {
                    continue;
                }

                $className = $cond->class->toString();
                $shortName = $this->extractShortClassName($className);

                if ($shortName === 'Success') {
                    $hasSuccess = true;
                } elseif ($shortName === 'Failure') {
                    $hasFailure = true;
                }
            }
        }

        if ($hasDefault || ($hasSuccess && $hasFailure)) {
            return null;
        }

        $missing = [];
        if (!$hasSuccess) {
            $missing[] = 'Success';
        }
        if (!$hasFailure) {
            $missing[] = 'Failure';
        }

        return RuleErrorBuilder::message(
            sprintf(
                'Match expression on Result type is not exhaustive. Missing: %s.',
                implode(', ', $missing)
            )
        )
            ->identifier('jsoizo.resultMatchNotExhaustive')
            ->build();
    }

    private function getExpressionKey(Node\Expr $expr): ?string
    {
        if ($expr instanceof Variable && is_string($expr->name)) {
            return '$' . $expr->name;
        }

        return null;
    }

    private function extractShortClassName(string $className): string
    {
        $parts = explode('\\', $className);
        return end($parts);
    }
}
