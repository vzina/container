<?php
/**
 * ContainerTest.php
 * PHP version 7
 *
 * @category container
 * @author   Weijian.Ye <yeweijian@3k.com>
 * @license  https://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/vzina
 * @date     2020-07-20
 */
declare(strict_types=1);

namespace EyPhpTest\Cases;


use EyPhp\Container\Container;
use EyPhpTest\Stub\Alias\SomeImplementation;
use EyPhpTest\Stub\Alias\SomeInterface;
use EyPhpTest\Stub\Foo;
use EyPhpTest\Stub\Person;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testBase()
    {
        $someImplementation = new SomeImplementation();
        $container = new Container();
        $container->set(SomeInterface::class, $someImplementation);

        $this->assertEquals($someImplementation, $container->get(SomeInterface::class));
    }

    public function testAlias()
    {
        $someImplementation = new SomeImplementation();
        $container = new Container();
        $container->set(SomeInterface::class, $someImplementation);
        $container->alias('Some', SomeInterface::class);

        $this->assertEquals($someImplementation, $container->get('Some'));
    }

    public function testBind()
    {
        $container = new Container();
        $container->bind(
            "Person",                       // key
            Person::class,  // FQCN
            ["Jane"],                  // constructor dependencies
            ["genre" => "Female"],     // attributes injection
            ["setAge" => [33]]    // call methods
        );

        /** @var Person $person */
        $person = $container->get('Person');
        $this->assertEquals(33, $person->getAge());
        $this->assertEquals('Jane', $person->getName());
        $this->assertEquals('Female', $person->genre);

        // 调用服务并覆盖声明的依赖项
        $person2 = $container->get(
            "Person",
            ["Mark"],
            ["genre" => "Male"],
            ["setAge" => [55]]
        );
        $this->assertEquals('Mark', $person2->getName()); // Mark
        $this->assertEquals(55, $person2->getAge());  // 55
        $this->assertEquals('Male', $person2->genre);     // Male
    }

    public function testDi()
    {
        $container = new Container();

        $container->bind(Foo::class, Foo::class);
        $foo = $container->get(Foo::class);
        $this->assertEquals(24, $foo->person->getAge());
        $this->assertEquals('Male', $foo->person->genre);


        $container->bind(Person::class, Person::class)
            ->setProperty('genre', 'Female')
            ->callMethod('setAge', [12]);
        $foo = $container->get(Foo::class);
        $this->assertEquals(12, $foo->person->getAge());
        $this->assertEquals('Female', $foo->person->genre);
    }
}