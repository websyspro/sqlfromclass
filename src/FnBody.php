<?php

namespace Websyspro\SqlFromClass;

use ReflectionFunction;

class FnBody
{
  public function __construct(
    public ReflectionFunction $reflectionFunction,
    public array $paramters,
    public string $body
  ){}
}