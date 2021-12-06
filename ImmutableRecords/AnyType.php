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
        $record = self::fromArrayViaDiscriminator($data);

        if ($record !== null) {
            return $record;
        }

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
     * @param array<mixed> $data
     *
     * @return static|null
     */
    public static function fromArrayViaDiscriminator(array $data)
    {
        $interfaces = class_implements(static::class);

        if ($interfaces === false || ! in_array(Discriminator::class, $interfaces)) {
            return null;
        }

        $discriminatorProperty = self::discriminatorProperty();

        if (! isset($data[$discriminatorProperty])) {
            throw new RuntimeException(
                sprintf(
                    'No discriminator property \'%s\' found to generate a \'%s\'.',
                    $discriminatorProperty,
                    static::class
                )
            );
        }

        $discriminatorMappingValue = $data[$discriminatorProperty];
        $discriminatorMapping = self::discriminatorMapping();

        if (! isset($discriminatorMapping[$discriminatorMappingValue])) {
            throw new RuntimeException(
                sprintf(
                    'Discriminator value \'%s\' is not a valid one for \'%s\'.',
                    $discriminatorMappingValue,
                    static::class
                )
            );
        }

        $model = $discriminatorMapping[$discriminatorMappingValue];

        return new self($model::fromArray($data));
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
