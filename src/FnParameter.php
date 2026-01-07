<?php

namespace Websyspro\SqlFromClass;

class FnParameter
{
  public function __construct(
    public string $name,
    public string $type,
  ){}   
}