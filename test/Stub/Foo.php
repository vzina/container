<?php
/**
 * Foo.php
 * PHP version 7
 *
 * @category container
 * @author   Weijian.Ye <yeweijian@3k.com>
 * @license  https://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/vzina
 * @date     2020-07-20
 */
declare(strict_types=1);

namespace EyPhpTest\Stub;


class Foo
{
    public $person;

    public function __construct(Person $person)
    {
        $this->person = $person;
    }
}