<?php

declare(strict_types=1);

/**
 * This file is part of Kooditorm/hyperf-basic.
 *
 * @link     https://github.com/Kooditorm/hyperf-basic
 * @contact  oswin.hu@gmail.com
 * @license  https://github.com/Kooditorm/hyperf-basict/blob/master/LICENSE
 */

namespace Kooditorm\Hyperf\Basic\VO;

use ArrayAccess;
use Hyperf\Contract\Arrayable;
use Hyperf\Contract\Jsonable;
use JsonSerializable;
use Kooditorm\Hyperf\Basic\Traits\HasAttributes;
use OpenApi\Attributes\Property;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use Throwable;
use Traversable;

/**
 * VO 基类（View Object）。
 *
 * 面向接口返回的展示对象，数据来源由业务层传入，不依赖请求上下文。
 * 支持按属性声明的类型自动把数组转换成嵌套 VO，字段由目标 VO 的白名单过滤。
 */
class AbstractBaseVo implements Jsonable, Arrayable, ArrayAccess, JsonSerializable
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
            if (! $property->isPublic() || $property->getDeclaringClass()->getName() !== $this::class) {
                continue;
            }

            $name = $property->getName();

            // 列表优先：只要带 items 注解就按 VO[] 处理，避免被对象类型声明抢走
            $ref = $this->resolveItemsRef($property);
            if (is_string($ref) && is_a($this->resolveClassName($ref), ViewObject::class, true)) {
                $this->voItemCasts[$name] = $this->resolveClassName($ref);
                continue;
            }

            $type = $property->getType();
            if ($type instanceof ReflectionNamedType
                && ! $type->isBuiltin()
                && is_a($type->getName(), ViewObject::class, true)
            ) {
                $this->voCasts[$name] = $type->getName();
            }
        }
    }

    /**
     * 取出数组元素的 VO 类型，支持两种写法：
     * - #[OA\Property(items: new OA\Items(ref: ConfigVo::class))]
     * - #[OA\Items(ref: ConfigVo::class)]
     */
    protected function resolveItemsRef(ReflectionProperty $property): ?string
    {
        foreach ($property->getAttributes(Property::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            try {
                $annotation = $attribute->newInstance();
            } catch (Throwable) {
                continue;
            }
            $items = $annotation->items ?? null;
            if ($items instanceof Items) {
                return $items->ref ?? null;
            }
        }

        // 独立写法 #[OA\Items(ref: ConfigVo::class)]
        $attributes = $property->getAttributes(Items::class);
        if ($attributes !== []) {
            return $attributes[0]->newInstance()->ref ?? null;
        }

        return null;
    }

    /**
     * 将注解里的 ref 解析为类名（支持 FQCN、短类名、#/components/schemas/X）
     */
    protected function resolveClassName(string $ref): string
    {
        if (class_exists($ref)) {
            return $ref;
        }
        if (str_starts_with($ref, '#/')) {
            $ref = substr($ref, (int) strrpos($ref, '/') + 1);
        }
        $namespace = (new ReflectionClass($this))->getNamespaceName();

        return str_contains($ref, '\\') ? $ref : $namespace . '\\' . $ref;
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