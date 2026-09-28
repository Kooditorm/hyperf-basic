<?php
declare(strict_types=1);

namespace Kooditorm\Hyperf\Basic\DTO;

use ArrayAccess;
use Hyperf\Contract\Arrayable;
use Hyperf\Contract\Jsonable;
use Hyperf\HttpServer\Contract\RequestInterface;
use JsonException;
use JsonSerializable;
use function Hyperf\Collection\collect;

class BaseDTO implements Jsonable, Arrayable, ArrayAccess, JsonSerializable
{
    protected array $attributes = [];

    protected array $attr = [];

    protected array $property = [];
    protected array $accessFields = [];

    public function __construct(protected RequestInterface $request)
    {
        $properties = $this->getProperties();
        if (!empty($properties)) {
            foreach ($properties as $key => $defaultValue) {
                if (!is_null($defaultValue)) {
                    $this->property[$key] = $defaultValue;
                }
                $this->accessFields[] = $key;
            }
        }
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
     * Dynamically retrieve attributes on the DTO.
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the DTO.
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
     * Determine if an attribute exists on the DTO.
     *
     * @param string $key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return $this->offsetExists($key);
    }

    /**
     * Unset an attribute on the DTO.
     *
     * @param string $key
     * @return void
     */
    public function __unset(string $key): void
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
        return isset($this->attributes[$offset]);
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
     * Get an attribute from the DTO.
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute(string $key): mixed
    {
        $this->loadAttrs();
        return $this->attributes[$key] ?? null;
    }

    /**
     * Set a given attribute on the DTO.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setAttribute(string $key, mixed $value): static
    {
        $this->attr[$key] = $value;
        return $this;
    }

    /**
     * Get all attributes.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        $this->loadAttrs();
        return $this->attributes;
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
     * Get allowed fields with default values
     *
     * @return array
     */
    protected function getProperties(): array
    {
        $reflection = new \ReflectionClass($this);
        $properties = [];

        foreach ($reflection->getProperties() as $property) {
            if ($property->isPublic() && $property->getDeclaringClass()->getName() === get_class($this)) {
                if ($property->isInitialized($this)) {
                    $properties[$property->getName()] = $property->getValue($this);
                } else {
                    $properties[$property->getName()] = null;
                }
            }
        }

        return $properties;
    }

    /**
     * @return void
     */
    protected function loadAttrs(): void
    {
        $data = $this->request->all();
        collect($data)->map(function ($value, $key) {
            if (in_array($key, $this->accessFields, true)) {
                $this->attributes[$key] = $value;
            }
        });
        $properties = $this->getProperties();
        foreach ($properties as $key => $defaultValue) {
            if (!empty($defaultValue) && (!isset($this->property[$key]) || $this->property[$key] !== $defaultValue)) {
                $this->attributes[$key] = $defaultValue;
            }
        }
        $this->attributes = array_merge($this->attributes, $this->attr);

    }
}