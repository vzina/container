<?php
/**
 * AstVisitorMetadata.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @contact yeweijian@eyugame.com
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\Generator;

use PhpParser\Node;

class AstVisitorMetadata
{
    public bool $hasConstructor = false;

    public ?Node\Stmt\ClassMethod $constructorNode = null;

    public ?bool $hasExtends = null;

    /**
     * The class name of \PhpParser\Node\Stmt\ClassLike.
     */
    public ?string $classLike = null;

    public function __construct(public string $className)
    {
    }
}
