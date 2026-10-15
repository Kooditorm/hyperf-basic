<?php

declare(strict_types=1);

/**
 * This file is part of Kooditorm/hyperf-basic.
 *
 * @link     https://github.com/Kooditorm/hyperf-basic
 * @contact  oswin.hu@gmail.com
 * @license  https://github.com/Kooditorm/hyperf-basict/blob/master/LICENSE
 */

namespace Kooditorm\Hyperf\Basic;

use ArrayAccess;
use Hyperf\Contract\Arrayable;
use Hyperf\Contract\Jsonable;
use JsonSerializable;
use Kooditorm\Hyperf\Basic\Annotations\ViewObject;
use Kooditorm\Hyperf\Basic\Traits\HasAttributes;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use Traversable;

/**
 * VO 基类（View Object）。
 *
 * 面向接口返回的展示对象，数据来源由业务层传入，不依赖请求上下文。
 * 支持按属性声明的类型自动把数组转换成嵌套 VO，字段由目标 VO 的白名单过滤。
 */
class AbstractBaseVo implements Arrayable, Jsonable, JsonSerializable, ArrayAccess
{
    use HasAttributes;

    /** 属性类型为 VO 的字段：[字段名 => VO 类名] */
    protected array $voCasts = [];

    /** 数组属性的元素类型为 VO 的字段：[字段名 => VO 类名] */
    protected array $voItemCasts = [];

    public function __construct(protected mixed $data = [])
    {
        $this->initProperties();
        $this->initPropertyCasts();
    }

    /**
     * VO 的数据来源：业务层传入的数组 / Arrayable 对象
     */
    protected function source(): array
    {
        if (is_array($this->data)) {
            return $this->data;
        }
        if ($this->data instanceof Arrayable) {
            return $this->data->toArray();
        }

        return [];
    }

    /**
     * 按属性声明的类型转换外部数据：
     * - 属性是 VO：数组自动实例化为对应 VO（字段由该 VO 的白名单过滤）
     * - 数组元素是 VO：逐个实例化
     */
    protected function castValue(string $key, mixed $value): mixed
    {
        if (isset($this->voCasts[$key])) {
            return $this->makeVo($this->voCasts[$key], $value);
        }

        if (isset($this->voItemCasts[$key])) {
            $items = $this->toItemArray($value);
            if ($items !== null) {
                return array_map(fn ($item) => $this->makeVo($this->voItemCasts[$key], $item), $items);
            }
        }

        return $value;
    }

    /**
     * 列表属性统一转成数组：支持 array / Arrayable（Collection、Model 集合）/ Traversable
     */
    protected function toItemArray(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }
        if ($value instanceof Arrayable) {
            return $value->toArray();
        }
        if ($value instanceof Traversable) {
            return iterator_to_array($value);
        }

        return null;
    }

    /**
     * 收集属性类型：
     * - 属性本身是 VO（如 public ConfigVo $config）
     * - 数组元素是 VO（如 public array $configs + #[OA\Items(ref: ConfigVo::class)]）
     */
    protected function initPropertyCasts(): void
    {
        $reflection = new ReflectionClass($this);
        foreach ($reflection->getProperties() as $property) {
            if (! $property->isPublic() || ! $this->isOwnProperty($property)) {
                continue;
            }

            $name = $property->getName();

            // 1) 原生注解：#[ArrayOf(ConfigVo::class)]
            if ($this->collectArrayOf($name, $property)) {
                continue;
            }


            $type = $property->getType();
            if ($type instanceof ReflectionNamedType
                && ! $type->isBuiltin()
                && $this->isNestedVo($type->getName())
            ) {
                $this->voCasts[$name] = $type->getName();
            }
        }
    }

    /**
     * 是否为可嵌套的 VO。
     *
     * 用 self::class（AbstractBaseVo）而非 ViewObject 接口判断：
     * 嵌套转换依赖 new $voClass($value) 的构造契约，这是基类提供的能力。
     * 注意必须用 self::class 而不是 static::class —— 后者在 UserVo/ConfigVo
     * 实例中会解析成子类自身，判断会失效。
     */
    protected function isNestedVo(string $class): bool
    {
        return is_a($class, self::class, true);
    }

    /**
     * 原生注解：#[ArrayOf(ConfigVo::class)]
     */
    protected function collectArrayOf(string $name, ReflectionProperty $property): bool
    {
        foreach ($property->getAttributes(ViewObject::class) as $attribute) {
            $class = $attribute->newInstance()->class;
            if ($this->isNestedVo($class)) {
                $this->voItemCasts[$name] = $class;

                return true;
            }
        }

        return false;
    }

    /**
     * 实例化嵌套 VO：
     * - 已是目标 VO / null：原样返回
     * - 数组或 Arrayable（Model、Collection 元素）：转换，字段由目标 VO 白名单过滤
     * - 其他（标量等）：原样保留，避免静默变成空 VO
     */
    protected function makeVo(string $voClass, mixed $value): mixed
    {
        if ($value instanceof $voClass) {
            return $value;
        }
        if ($value === null) {
            return null;
        }
        if (is_array($value) || $value instanceof Arrayable) {
            return new $voClass($value);
        }

        return $value;
    }
}