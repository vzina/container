<?php
/**
 * AstParser.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\Generator;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use OpenEf\Container\Composer;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

class AstParser
{
    public const TYPES = [
        'int',
        'float',
        'string',
        'bool',
        'array',
        'object',
        'resource',
        'mixed',
        'null',
    ];

    protected static ?self $instance = null;

    protected Parser $astParser;
    protected PrettyPrinterAbstract $printer;

    public function __construct()
    {
        $this->printer = new Standard();
        $this->astParser = (new ParserFactory())->createForVersion(PhpVersion::fromComponents(8, 0));
    }

    public static function getInstance(): self
    {
        if (static::$instance) {
            return static::$instance;
        }
        return static::$instance = new static();
    }

    public function parse(string $code): ?array
    {
        return $this->astParser->parse($code);
    }

    public function parseClassByStmts(array $stmts): string
    {
        $namespace = $className = '';
        foreach ($stmts as $stmt) {
            if ($stmt instanceof Namespace_ && $stmt->name) {
                $namespace = $stmt->name->toString();
                foreach ($stmt->stmts as $node) {
                    if (($node instanceof ClassLike) && $node->name) {
                        $className = $node->name->toString();
                        break;
                    }
                }
            }
        }
        return ($namespace && $className) ? $namespace . '\\' . $className : '';
    }

    public function proxy(string $className)
    {
        $code = Composer::getCodeByClassName($className);
        $stmts = $this->parse($code);
        $traverser = new NodeTraverser();
        $visitorMetadata = new AstVisitorMetadata($className);
        $queue = clone AstVisitorRegistry::getQueue();
        foreach ($queue as $string) {
            $visitor = new $string($visitorMetadata);
            $traverser->addVisitor($visitor);
        }
        $modifiedStmts = $traverser->traverse($stmts);

        return $this->printer->prettyPrintFile($modifiedStmts);
    }

    /**
     * @return null|Node\Stmt[]
     */
    public function getNodesFromReflectionClass(ReflectionClass $reflectionClass): ?array
    {
        return $this->parse(file_get_contents($reflectionClass->getFileName()));
    }

    public function getNodeFromReflectionType(ReflectionType $reflection): Node\ComplexType|Node\Identifier|Node\Name
    {
        if ($reflection instanceof ReflectionUnionType) {
            $unionType = [];
            foreach ($reflection->getTypes() as $objType) {
                $type = $objType->getName();
                if (! in_array($type, static::TYPES)) {
                    $unionType[] = new Node\Name('\\' . $type);
                } else {
                    $unionType[] = new Node\Identifier($type);
                }
            }
            return new Node\UnionType($unionType);
        }

        return $this->getTypeWithNullableOrNot($reflection);
    }

    public function getNodeFromReflectionParameter(ReflectionParameter $parameter): Node\Param
    {
        $result = new Node\Param(
            new Node\Expr\Variable($parameter->getName())
        );

        if ($parameter->isDefaultValueAvailable()) {
            $result->default = $this->getExprFromValue($parameter->getDefaultValue());
        }

        if ($parameter->hasType()) {
            $result->type = $this->getNodeFromReflectionType($parameter->getType());
        }

        if ($parameter->isPassedByReference()) {
            $result->byRef = true;
        }

        if ($parameter->isVariadic()) {
            $result->variadic = true;
        }

        return $result;
    }

    public function getExprFromValue($value): Node\Expr
    {
        return match (gettype($value)) {
            'array' => value(function ($value) {
                $isList = ! Arr::isAssoc($value);
                $result = [];
                foreach ($value as $i => $item) {
                    $key = null;
                    if (! $isList) {
                        $key = is_int($i) ? new Node\Scalar\LNumber($i) : new Node\Scalar\String_($i);
                    }
                    $result[] = new Node\Expr\ArrayItem($this->getExprFromValue($item), $key);
                }
                return new Node\Expr\Array_($result, [
                    'kind' => Node\Expr\Array_::KIND_SHORT,
                ]);
            }, $value),
            'string' => new Node\Scalar\String_($value),
            'integer' => new Node\Scalar\LNumber($value),
            'double' => new Node\Scalar\DNumber($value),
            'NULL' => new Node\Expr\ConstFetch(new Node\Name('null')),
            'boolean' => new Node\Expr\ConstFetch(new Node\Name($value ? 'true' : 'false')),
            'object' => $this->getExprFromObject($value),
            default => throw new InvalidArgumentException($value . ' is invalid'),
        };
    }

    /**
     * @return Node\Stmt\ClassMethod[]
     */
    public function getAllMethodsFromStmts(array $stmts, bool $withTrait = false): array
    {
        $methods = [];
        foreach ($stmts as $namespace) {
            if (! $namespace instanceof Node\Stmt\Namespace_) {
                continue;
            }

            /** @var string[] $uses */
            $uses = [];

            foreach ($namespace->stmts as $class) {
                if ($class instanceof Node\Stmt\Use_) {
                    foreach ($class->uses as $use) {
                        $uses[$use->name->getLast()] = $use->name->toString();
                    }
                    continue;
                }

                if (! $class instanceof Node\Stmt\Class_ && ! $class instanceof Node\Stmt\Interface_ && ! $class instanceof Node\Stmt\Trait_) {
                    continue;
                }

                foreach ($class->getMethods() as $method) {
                    $methods[] = $method;
                }

                if ($withTrait) {
                    foreach ($class->stmts as $stmt) {
                        if ($stmt instanceof Node\Stmt\TraitUse) {
                            foreach ($stmt->traits as $trait) {
                                if (isset($uses[$trait->getFirst()])) {
                                    $traitName = $uses[$trait->getFirst()] . substr($trait->toString(), strlen($trait->getFirst()));
                                } else {
                                    if (count($trait->getParts()) == 1) {
                                        $traitName = $namespace->name->toString() . '\\' . $trait->toString();
                                    } else {
                                        $traitName = $trait->toString();
                                    }
                                }
                                $traitNodes = $this->getNodesFromReflectionClass(new ReflectionClass($traitName));
                                $methods = array_merge($methods, $this->getAllMethodsFromStmts($traitNodes, true));
                            }
                        }
                    }
                }
            }
        }

        return $methods;
    }

    private function getExprFromObject(object $value)
    {
        $ref = new ReflectionClass($value);
        if (method_exists($ref, 'isEnum') && $ref->isEnum()) {
            return new Node\Expr\ClassConstFetch(
                new Node\Name('\\' . $value::class),
                $value->name
            );
        }

        return new Node\Expr\New_(
            new Node\Name\FullyQualified($value::class)
        );
    }

    private function getTypeWithNullableOrNot(ReflectionType $reflection): Node\ComplexType|Node\Identifier|Node\Name
    {
        if (! $reflection instanceof ReflectionNamedType) {
            throw new ReflectionException('ReflectionType must be ReflectionNamedType.');
        }

        $name = $reflection->getName();

        if ($reflection->allowsNull() && $name !== 'mixed') {
            return new Node\NullableType($this->getTypeFromString($name));
        }

        if (! in_array($name, static::TYPES)) {
            return new Node\Name('\\' . $name);
        }
        return new Node\Identifier($name);
    }

    private function getTypeFromString(string $name)
    {
        if (! in_array($name, static::TYPES)) {
            return '\\' . $name;
        }
        return $name;
    }
}
