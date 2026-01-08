<?php

namespace Websyspro\SqlFromClass;

use Websyspro\Commons\Collection;
use Websyspro\SqlFromClass\Enums\Token;

class TokenList
{
  public function __construct(
    public Token $taken,
    public string|Collection $value,
    public string|null $entity = null,
    public string|null $entityName = null,
    public bool|null $entityIsPrimary = false
  ){}
}