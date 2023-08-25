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
        yield from $this->fields;
    }

    public function getFromKey(string $key): FieldData
    {
        return $this->fields[$key] ?? throw new \OutOfBoundsException("Ключ \"$key\" не существует");
    }
}
