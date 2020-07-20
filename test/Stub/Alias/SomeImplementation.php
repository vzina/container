<?php
/**
 * SomeImplementation.php
 * PHP version 7
 *
 * @category container
 * @author   Weijian.Ye <yeweijian@3k.com>
 * @license  https://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/vzina
 * @date     2020-07-20
 */
declare(strict_types=1);

namespace EyPhpTest\Stub\Alias;


class SomeImplementation implements SomeInterface
{

    public function name()
    {
        return __CLASS__;
    }
}