<?php

namespace Websyspro\SqlFromClass\Interfaces;

class LeftJoin
{
  public function __construct(
    public string $table,
    public string $column,
    public string $joinTable,
    public string $joinColumn    
  ){}
}