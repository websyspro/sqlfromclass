<?php

namespace Websyspro\SqlFromClass;

use ReflectionFunction;
use Websyspro\Commons\Collection;

class FnBody
{
  public function __construct(
    public ReflectionFunction $reflectionFunction,
    public Collection $paramters,
    public string $body
  ){}
}