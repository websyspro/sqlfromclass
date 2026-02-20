<?php

namespace Websyspro\SqlFromClass;

use Websyspro\Commons\Collection;
use Websyspro\SqlFromClass\Enums\EntityPriority;
use Websyspro\SqlFromClass\Enums\TokenType;
use Websyspro\SqlFromClass\Interfaces\LeftJoin;

class Token
{
  public function __construct(
    public TokenType $takenType,
    public string|Collection $value,
    public string|null $entity = null,
    public string|null $entityName = null,
    public string|null $entityField = null,
    public LeftJoin|null $leftJoin = null,
    public EntityPriority|null $entityPriority = null,
  ){}
}