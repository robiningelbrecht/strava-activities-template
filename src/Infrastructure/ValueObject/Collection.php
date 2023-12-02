<?php

namespace App\Infrastructure\ValueObject;

/**
 * @template T
 */
abstract class Collection implements \Countable, \IteratorAggregate, \JsonSerializable
{
    /** @var array<T> */
    private array $items = [];

    abstract public function getItemClassName(): string;

    public static function empty(): static
    {
        return new static([]);
    }

    /**
     * @param array<T> $items
     */
    public static function fromArray(array $items): static
    {
        return new static($items);
    }

    /**
     * @param array<T> $items
     */
    final private function __construct(array $items)
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    /**
     * @param T $item
     */
    public function has($item): bool
    {
        $this->guardItemIsInstanceOfItemClassName($item);

        return \in_array($item, $this->items);
    }

    /**
     * @param T $item
     */
    public function add($item): self
    {
        $this->guardItemIsInstanceOfItemClassName($item);
        $this->items[] = $item;

        return $this;
    }

    public function mergeWith(Collection $collection): self
    {
        foreach ($collection as $item) {
            $this->add($item);
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        return 0 === $this->count();
    }

    public function count(): int
    {
        return \count($this->items);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @return array<T>
     */
    public function toArray(): array
    {
        return \array_values($this->items);
    }

    /**
     * @return T|false
     */
    public function getLast(): mixed
    {
        $items = $this->toArray();

        return end($items);
    }

    public function reverse(): static
    {
        return static::fromArray(array_reverse($this->toArray()));
    }

    /**
     * @return array<T>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param T $item
     */
    private function guardItemIsInstanceOfItemClassName($item): void
    {
        $itemClassName = $this->getItemClassName();
        if (!$item instanceof $itemClassName) {
            throw new \InvalidArgumentException(sprintf('Item must be an instance of %s', $itemClassName));
        }
    }
}
