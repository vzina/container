<?php
/**
 * NetteGenerator.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Closure;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\TraitType;

class NetteGenerator implements CodeGeneratorInterface
{
    public function genProxyCode(string $className, string $sourcePath): string
    {
        $phpFile = PhpFile::fromCode(file_get_contents($sourcePath));

        $classes = $phpFile->getClasses();
        foreach ($classes as $name => $class) {
            if ($className !== $name) {
                continue;
            }

            $this->genProxyClass($className, $class);
            break;
        }

        return (string)$phpFile;
    }

    /**
     * @param ClassType|InterfaceType|TraitType|EnumType $class
     * @author  weijian.ye
     */
    protected function genProxyClass(string $className, $class): void
    {
        $isUseTrait = false;
        $methods = $class->getMethods();
        foreach ($methods as $method) {
            if (! $this->shouldRewriteMethod($className, $method->getName())) {
                continue;
            }

            if (! $isUseTrait) {
                $isUseTrait = true;
                $class->addTrait(ProxyTrait::class);
                $class->addTrait(PropertyTrait::class);
            }

            $parameters = $method->getParameters();
            $fn = new Closure();
            $fn->setParameters($parameters);
            $fn->setBody($method->getBody());

            $params = [];
            foreach ($parameters as $name => $parameter) {
                $defaultValue = $parameter->getDefaultValue();
                if ($defaultValue instanceof Literal) {
                    if (preg_match('#(\\\.*)\(#', (string)$defaultValue, $r)) {
                        $defaultValue = Literal::new('\\' . DefaultLiteral::class, [$r[1]]);
                    }
                }

                $params[$name] = $defaultValue;
            }

            $method->setBody(<<<CODE
return self::__proxyCall(
    __CLASS__, 
    __FUNCTION__, 
    self::__getParamsMap(?, func_get_args(), ?), 
    {$fn});
CODE, [$params, $method->isVariadic()]);
        }

        if ($isUseTrait) {
            $constructor = '__construct';
            if ($class->hasMethod($constructor)) {
                $constructorMethod = $class->getMethod('__construct');
                $body = '$this->__handlePropertyHandler(__CLASS__);' . PHP_EOL . $constructorMethod->getBody();
                $constructorMethod->setBody($body);
            } else {
                $constructorMethod = $class->addMethod('__construct');
                if ($class->getExtends()) {
                    $constructorMethod->addBody('if (method_exists(parent::class, \'__construct\')) {');
                    $constructorMethod->addBody('    parent::__construct(...func_get_args());');
                    $constructorMethod->addBody('}');
                }
                $constructorMethod->addBody('$this->__handlePropertyHandler(__CLASS__);');
            }
        }
    }

    private function shouldRewriteMethod(string $className, string $method): bool
    {
        return AspectParser::parse($className)->shouldRewrite($method);
    }
}
