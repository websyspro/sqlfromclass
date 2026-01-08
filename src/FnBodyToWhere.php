<?php

namespace Websyspro\SqlFromClass;

use ReflectionFunction;
use Websyspro\Commons\Collection;

class FnBodyToWhere
{
  public function __construct(
    public ReflectionFunction $reflectionFunction,
    public Collection $paramters,
    public Collection $static,
    public Collection $body
  ){}
}