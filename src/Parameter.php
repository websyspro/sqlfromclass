<?php

namespace Websyspro\SqlFromClass;

class Parameter
{
  public function __construct(
    public string $name,
    public string $entity,
    public array|null $columns = []
  ){}   
}