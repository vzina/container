<?php
/**
 * RewriteCollection.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\Generator;

class RewriteCollection
{
    public const CLASS_LEVEL = 1;

    public const METHOD_LEVEL = 2;

    /**
     * Which methods can be rewrite.
     * @var array
     */
    protected $methods = [];

    /**
     * Method pattern.
     * @var array
     */
    protected $pattern = [];

    /**
     * Rewrite level.
     * @var int
     */
    protected $level = self::METHOD_LEVEL;

    /**
     * @var array
     */
    protected $shouldNotRewriteMethods = [
        '__construct',
    ];

    public function __construct(protected string $class)
    {
    }

    /**
     * @param array|string $methods
     */
    public function add($methods): self
    {
        $methods = (array) $methods;
        foreach ($methods as $method) {
            if (strpos($method, '*') === false) {
                $this->methods[] = $method;
            } else {
                $preg = str_replace(['*', '\\'], ['.*', '\\\\'], $method);
                $this->pattern[] = "/^{$preg}$/";
            }
        }

        return $this;
    }

    public function shouldRewrite(string $method): bool
    {
        if ($this->level === self::CLASS_LEVEL) {
            return ! in_array($method, $this->shouldNotRewriteMethods);
        }

        if (in_array($method, $this->methods)) {
            return true;
        }

        foreach ($this->pattern as $pattern) {
            if (preg_match($pattern, $method)) {
                return true;
            }
        }

        return false;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;
        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Return the methods that should not rewrite.
     */
    public function getShouldNotRewriteMethods(): array
    {
        return $this->shouldNotRewriteMethods;
    }
}
