<?php

namespace Websyspro\SqlFromClass\Interfaces;

class Froms
{
  public function __construct(
    public string $entity,
    public bool $innerJoin = false
  ){}
}