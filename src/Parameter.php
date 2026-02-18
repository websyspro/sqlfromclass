<?php

namespace Websyspro\SqlFromClass;

use Websyspro\Entity\Shareds\EntityStructure;

class Parameter
{
  public function __construct(
    public string $name,
    public string $entity,
    public EntityStructure|null $entityStructure = null
  ){}   
}