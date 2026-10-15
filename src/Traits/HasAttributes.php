<?php

declare(strict_types=1);

/**
 * This file is part of Kooditorm/hyperf-basic.
 *
 * @link     https://github.com/Kooditorm/hyperf-basic
 * @contact  oswin.hu@gmail.com
 * @license  https://github.com/Kooditorm/hyperf-basict/blob/master/LICENSE
 */

namespace Kooditorm\Hyperf\Basic\Traits;

use Hyperf\Contract\Arrayable;
use JsonException;
use ReflectionClass;
use ReflectionProperty;
use function Hyperf\Collection\collect;

/**
 * DTO / VO 的公共属性容器。
 *
 * 提供统一的属性读写、默认值合并与序列化行为；
 * 数据来源差异由实现类通过 source() 决定：
 * - DTO：入站请求
 * - VO：服务层返回结果
 */
trait HasAttributes
{
    /** 对外暴露的属性集合 */
    protected array $attributes = [];

    /** 通过 setAttribute() 显式设置的属性 */
    protected array $attr = [];

    /** 类声明时的属性默认值 */
    protected array $property = [];

    /** 允许被外部数据赋值的字段白名单 */
    protected array $accessFields = [];

    /**
     * 数据来源
     */
    abstract protected function source(): array;

    /**
     * 初始化字段白名单与默认值
     */
    protected function initProperties(): void
    {
        $properties = $this->getProperties();
        if (empty($properties)) {
            return;
        }
        foreach ($properties as $key => $defaultValue) {
            if (! is_null($defaultValue)) {
                $this->property[$key] = $defaultValue;
            }
            $this->accessFields[] = $key;
        }
    }

    /**
     * @throws JsonException
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * Dynamically retrieve attributes on the object.
     */
    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the object.
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if an attribute exists on the object.
     */
    public function __isset(string $key): bool
    {
        return $this->offsetExists($key);
    }

    /**
     * Unset an attribute on the object.
     */
    public function __unset(string $key): void
    {
        $this->offsetUnset($key);
    }

    /**
     * Get the value for a given offset.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->getAttribute($offset);
    }

    /**
     * Set the value for a given offset.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * Determine if the given attribute exists.
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * Unset the value for a given offset.
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset]);
    }

    /**
     * Get an attribute from the object.
     */
    public function getAttribute(string $key): mixed
    {
        $this->loadAttrs();
        return $this->attributes[$key] ?? null;
    }

    /**
     * Set a given attribute on the object.
     */
    public function setAttribute(string $key, mixed $value): static
    {
        $this->attr[$key] = $value;
        return $this;
    }

    /**
     * Get all attributes.
     */
    public function getAttributes(): array
    {
        $this->loadAttrs();
        return $this->attributes;
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return $this->normalizeArray($this->getAttributes());
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @throws JsonException
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR | $options);
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get allowed fields with default values
     *
     * @return array
     */
    protected function getProperties(): array
    {
        $reflection = new ReflectionClass($this);
        $properties = [];

        foreach ($reflection->getProperties() as $property) {
            if ($property->isPublic() && $this->isOwnProperty($property)) {
                $properties[$property->getName()] = $property->isInitialized($this)
                    ? $property->getValue($this)
                    : null;
            }
        }

        return $properties;
    }

    /**
     * 属性是否参与填充：当前类自身声明，或继承自中间基类（如 AbstractPaginateVo）。
     *
     * 顶层基类（AbstractBaseVo / AbstractBaseDTO，即本 trait 的使用类）自身声明的
     * 属性不参与，避免把容器字段混进业务字段白名单。
     */
    protected function isOwnProperty(ReflectionProperty $property): bool
    {
        $declaring = $property->getDeclaringClass()->getName();
        if ($declaring === self::class) {
            return false;
        }

        return $declaring === $this::class || is_subclass_of($this::class, $declaring);
    }

    /**
     * 载入属性，优先级：外部数据 < 类属性默认值 < 显式赋值
     *
     * @return void
     */
    protected function loadAttrs(): void
    {
        collect($this->source())->map(function ($value, $key) {
            if (in_array($key, $this->accessFields, true)) {
                $this->attributes[$key] = $this->castValue($key, $value);
            }
        });

        foreach ($this->getProperties() as $key => $defaultValue) {
            if (! empty($defaultValue) && (! isset($this->property[$key]) || $this->property[$key] !== $defaultValue)) {
                $this->attributes[$key] = $defaultValue;
            }
        }

        $this->attributes = array_merge($this->attributes, $this->attr);
    }

    /**
     * 外部数据进入时的类型转换钩子，默认原样返回。
     * VO 可覆写实现嵌套 VO 的自动实例化。
     */
    protected function castValue(string $key, mixed $value): mixed
    {
        return $value;
    }

    /**
     * 递归序列化嵌套对象（VO / DTO / 数组）
     */
    protected function normalizeArray(array $attributes): array
    {
        foreach ($attributes as $key => $value) {
            $attributes[$key] = $this->normalizeValue($value);
        }

        return $attributes;
    }

    protected function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof Arrayable) {
            return $this->normalizeArray($value->toArray());
        }
        if (is_array($value)) {
            return $this->normalizeArray($value);
        }

        return $value;
    }
}
