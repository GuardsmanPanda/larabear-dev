<?php

namespace GuardsmanPanda\LarabearDev\Infrastructure\Database\Internal;

class EloquentModelColumnInternal {

    public function __construct(
        public readonly string      $columnName,
        public readonly string      $nativeDataType,
        public readonly string      $phpDataType,
        public int                  $sortOrder,
        public readonly bool        $isNullable,
        public readonly string      $requiredHeader,
        public readonly string|null $eloquentCast,
    ) {
        if ($this->isNullable) {
            ++$this->sortOrder;
        }
    }

}
