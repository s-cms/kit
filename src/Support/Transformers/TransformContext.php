<?php

namespace SmartCms\Kit\Support\Transformers;

class TransformContext
{
    public function __construct(
        public mixed $page,
        public array $data = [],
        public array $options = []
    ) {}

    /**
     * Add data to the transformation result.
     */
    public function add(string $key, mixed $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Merge data into the transformation result.
     */
    public function merge(array $data): self
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * Check if a key exists in the data.
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Get a value from the data.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Remove a key from the data.
     */
    public function remove(string $key): self
    {
        unset($this->data[$key]);

        return $this;
    }

    /**
     * Check if an option is set.
     */
    public function hasOption(string $key): bool
    {
        return isset($this->options[$key]);
    }

    /**
     * Get an option value.
     */
    public function option(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
