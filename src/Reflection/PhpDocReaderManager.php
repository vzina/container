<?php
/**
 * PhpDocReaderManager.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\Reflection;

use PhpDocReader\PhpDocReader;

class PhpDocReaderManager
{
    protected static ?PhpDocReader $instance = null;

    public static function getInstance(): PhpDocReader
    {
        if (static::$instance) {
            return static::$instance;
        }
        return static::$instance = new PhpDocReader();
    }
}
