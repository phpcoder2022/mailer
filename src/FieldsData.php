<?php

namespace Phpcoder2022\SimpleMailer;

/**
 * @template-implements \IteratorAggregate<string, FieldData>
 */
abstract class FieldsData implements \Countable, \IteratorAggregate
{
    /** @var array<string, FieldData> $fields */
    protected array $fields;

    protected function __construct(FieldData ...$fields)
    {
        $this->fields = [];
        foreach ($fields as $field) {
            if (isset($this->fields[$field->key])) {
                throw new \OverflowException("Ключ \"$field->key\" уже существует");
            }
            $this->fields[$field->key] = $field;
        }
    }

    abstract public static function createWithData(): FieldsData;

    public function count(): int
    {
        return count($this->fields);
    }

    /** @return \Traversable<string, FieldData> */
    public function getIterator(): \Traversable
    {
        foreach ($this->fields as $key => $field) {
            yield $key => $field;
        }
    }

    public function getFromKey(string $key): FieldData
    {
        if (array_key_exists($key, $this->fields)) {
            return $this->fields[$key];
        } else {
            throw new \OutOfBoundsException("Ключ \"$key\" не существует");
        }
    }
}
