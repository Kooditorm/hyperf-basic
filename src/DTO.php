<?php
declare(strict_types=1);

namespace Kooditorm\Hyperf\Basic;

use Hyperf\Contract\Arrayable;
use Hyperf\Contract\Jsonable;
use Hyperf\Database\Model\Concerns\HasAttributes;
use Hyperf\HttpServer\Contract\RequestInterface;
use ArrayAccess;
use Hyperf\Stringable\Str;
use JsonException;
use JsonSerializable;
class DTO implements Jsonable, Arrayable, ArrayAccess, JsonSerializable
{
    use HasAttributes;
    public function __construct(protected RequestInterface $request)
    {
        $this->init();
    }

    public function init(): void
    {
        $this->getField();
    }


    /**
     * @return string
     * @throws JsonException
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param string $key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return $this->offsetExists($key);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param string $key
     * @return void
     */
    public function __unset(string $key)
    {
        $this->offsetUnset($key);
    }

    /**
     * Get the value for a given offset.
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->getAttribute($offset);
    }

    /**
     * Set the value for a given offset.
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * Determine if the given attribute exists.
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return !is_null($this->getAttribute($offset));
    }

    /**
     * Unset the value for a given offset.
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset]);
    }

    /**
     * Set a given attribute on the model.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function setAttribute(string $key, mixed $value): mixed
    {
        if ($this->hasSetMutator($key)) {
            return $this->setMutatedAttributeValue($key, $value);
        }

        if ($value && $this->isDateAttribute($key)) {
            $value = $this->fromDateTime($value);
        }

        if ($this->isClassCastable($key)) {
            $this->setClassCastableAttribute($key, $value);
            return $this;
        }

        if (!is_null($value) && $this->isJsonCastable($key)) {
            $value = $this->castAttributeAsJson($key, $value);
        }

        if (Str::contains($key, '->')) {
            return $this->fillJsonAttribute($key, $value);
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->getAttributes();
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     * @return string
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
     * Get allowed fields
     *
     * @return array
     */
    protected function getField(): array
    {
        $reflection = new \ReflectionClass($this);
        $properties = [];

        foreach ($reflection->getProperties() as $property) {
            if ($property->isPublic() && $property->getDeclaringClass()->getName() === get_class($this)) {
                $properties[] = $property->getName();
            }
        }

        print_r($properties);


        return [];
    }
}