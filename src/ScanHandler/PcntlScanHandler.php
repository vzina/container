<?php
/**
 * PcntlScanHandler.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\ScanHandler;

use RuntimeException;

class PcntlScanHandler implements ScanHandlerInterface
{
    public function __construct()
    {
        if (! extension_loaded('pcntl')) {
            throw new RuntimeException('Missing pcntl extension.');
        }
    }

    public function scan(): Scanned
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            throw new RuntimeException('The process fork failed');
        }
        if ($pid) {
            pcntl_wait($status);
            return new Scanned(true);
        }

        return new Scanned(false);
    }
}
