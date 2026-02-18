<?php

namespace Websyspro\SqlFromClass\Interfaces;

class ParamIndex
{
  public function __construct(
    public int $index,
    public string $value,
  ){}

  public function getAlias(
  ): string {
    return "$[Param_{$this->index}]";
  }
}