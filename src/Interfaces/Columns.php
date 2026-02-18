<?php

namespace Websyspro\SqlFromClass\Interfaces;

class Columns
{
  public function __construct(
    public string $name,
    public string $entity
  ){}
}