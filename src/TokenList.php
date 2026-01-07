<?php

namespace Websyspro\SqlFromClass;

use Websyspro\SqlFromClass\Enums\Token;

class TokenList
{
  public function __construct(
    public Token $taken,
    public string $value,
  ) {
  }
}