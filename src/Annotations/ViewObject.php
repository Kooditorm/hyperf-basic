<?php

declare(strict_types=1);

/**
 * This file is part of Kooditorm/hyperf-basic.
 *
 * @link     https://github.com/Kooditorm/hyperf-basic
 * @contact  oswin.hu@gmail.com
 * @license  https://github.com/Kooditorm/hyperf-basict/blob/master/LICENSE
 */

namespace Kooditorm\Hyperf\Basic\Annotations;

use Attribute;
/**
 * 声明数组属性的元素类型，用于 VO 自动实例化嵌套对象。
 *
 * 与文档注解（OA\Property / OA\Items）解耦：
 * 文档归文档，运行时转换归这里，不依赖任何第三方注解组件。
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ViewObject
{
    public function __construct(public string $class)
    {
    }
}