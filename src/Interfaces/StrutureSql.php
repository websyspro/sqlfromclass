<?php

namespace Websyspro\SqlFromClass\Interfaces;

use Websyspro\Commons\Collection;

class StrutureSql
{
  public function __construct(
    public Collection|null $columns = null,
    public Collection|null $froms = null,
    public Collection|null $wheres = null,
    public Collection|null $params = null,
  ){}
}