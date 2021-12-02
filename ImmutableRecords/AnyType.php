<?php

declare(strict_types=1);

namespace Elastic\OpenApi\Codegen\ImmutableRecords;

use RuntimeException;
use Throwable;

trait AnyType
{
    /** @var mixed */
    private $value;

    /**
     * @param mixed $value
     */
    private function __construct($value)
    {
        if ($value === null) {
            throw new RuntimeException(
                'Data could not be transformed into one of the following models: \'%s\'.',
                print_r(static::models(), true)
            );
        }

        $this->value = $value;
    }

    /**
     * @param array<mixed> $data
     *
     * @return static
     */
    public static function fromArray(array $data)
    {
        $value = null;

        foreach (static::models() as $model) {
            try {
                $value = $model::fromArray($data);

                return new self($value);
            } catch (Throwable $exception) {
            }
        }

        throw new RuntimeException(
            sprintf('No model matches the given data for class \'%s\'.', static::class)
        );
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return $this->value->toArray();
    }

    /**
     * @return mixed
     */
    public function toValue()
    {
        return $this->value;
    }

    /**
     * @return array<class-string>
     */
    private static function models(): array
    {
        return [];
    }
}
