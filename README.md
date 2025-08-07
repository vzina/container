# OpenEf Container

OpenEf Container 是一个基于 PHP 的 AOP（面向切面编程）容器组件，提供了依赖注入、注解解析、代理生成等功能，帮助开发者实现代码解耦和横切关注点分离。


## 安装说明

### 环境要求
- PHP >= 8.2
- Composer

### 安装步骤
通过 Composer 安装组件：
```bash
composer require open-ef/container
```

## 核心功能

1. **依赖注入**  
   通过 `@Inject` 注解实现属性自动注入，支持类型提示和 PHP 文档注释解析。

   ```php
   use OpenEf\Container\Annotation\Inject;
   use OpenEf\Container\Annotation\Depend;

   #[Depend]
   class UserService
   {
       #[Inject]
       private LoggerInterface $logger;
   }
   ```

2. **AOP 切面支持**  
   通过注解或配置定义切面，实现方法拦截、增强等功能。

   ```php
   // 定义切面类
   use OpenEf\Container\Generator\ProceedingJoinPoint;
   
   #[Aspect]
   class LogAspect
   {
        public $classes = [
            '*',
        ];

        public function process(ProceedingJoinPoint $proceedingJoinPoint)
        {
            return $proceedingJoinPoint->process();
        }
    }
   }
   ```

3. **容器管理**  
   基于 PSR-11 标准的容器实现，支持依赖解析和对象生命周期管理。

   ```php
   use OpenEf\Container\ContainerFactory;
   use OpenEf\Framework\Config\ConfigFactory;

   $container = ContainerFactory::make();
   $userService = $container->get(UserService::class);
   ```


## 配置说明

### 基础配置
组件配置可通过 `ScanConfig` 进行自定义，支持以下关键配置：

- `cacheable`：是否启用缓存（默认：`false`）
- `paths`：需要扫描的目录路径
- `aspects`：需要加载的切面类
- `collectors`：需要加载的收集类
- `class_map`：需要加载的映射类

### 组件配置示例
```php
final class ComponentProvider
{
    public function __invoke(ContainerInterface $container)
    {
        // 服务注入
        $container[ApplicationInterface::class] = fn () => new Application();
        // 配置组件内容
        $container->extend(ScanConfig::class, fn(ScanConfig $sc) => $sc->merge([
            'paths' => [
                __DIR__,
            ],
            'collectors' => [],
            'class_map' => [],
            'aspects' => [],
        ]));
    }
}
```

## 使用示例

### 1. 依赖注入示例
```php
use OpenEf\Container\ContainerFactory;
use OpenEf\Container\Annotation\Depend;

#[Depend]
class PaymentService
{
    #[Inject]
    private LoggerInterface $logger;
    
    public function pay(float $amount)
    {
        $this->logger->info("支付金额：{$amount}");
        // 支付逻辑...
    }
}

// 使用容器获取实例
$container = ContainerFactory::make();
$payment = $container->get(PaymentService::class);
$payment->pay(100.0);
```

### 2. AOP 切面示例
```php
use OpenEf\Container\Generator\ProceedingJoinPoint;
use OpenEf\Container\Annotation\Depend;

// 定义切面
#[Aspect]
class TransactionAspect
{
    public $classes = [
        'OrderService::create*',
    ];
    
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        try {
            // 开启事务
            DB::beginTransaction();
            $result = $proceedingJoinPoint->process();
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

// 目标服务
#[Depend]
class OrderService
{
    public function createOrder(array $data)
    {
        // 创建订单逻辑...
    }
}

// 使用容器获取实例
$container = ContainerFactory::make();
$order = $container->get(OrderService::class);
$order->createOrder(['money' => 100.0]);
```


## 测试运行

组件内置 PHPUnit 测试用例，可通过以下命令运行：
```bash
# 安装开发依赖
composer install --dev

# 执行测试
vendor/bin/phpunit
```


## 许可证
本组件基于 MIT 许可证开源，详情参见 [LICENSE](LICENSE) 文件。
