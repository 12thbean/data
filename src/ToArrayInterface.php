<?php

namespace Zendrop\Data;

use Illuminate\Contracts\Support\Arrayable;

interface ToArrayInterface extends Arrayable
{
    public function toArray(ToArrayCase $toCase = ToArrayCase::Snake): array;
}
