<?php
/**
 * ScanHandlerInterface.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\ScanHandler;

interface ScanHandlerInterface
{
    public function scan(): Scanned;
}
