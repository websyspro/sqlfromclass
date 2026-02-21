<?php

namespace Websyspro\SqlFromClass\Interfaces;

use Websyspro\Entity\Shareds\ForeignKey;

class EntityJoin
{
  public string $table;
  public string $key;
  public string $joinTable;
  public string $joinKey;   

  public function __construct(
    public ForeignKey|null $foreignKey = null
  ){
    $this->key = $foreignKey->key;
    $this->table = $foreignKey->entity->table;
    $this->joinKey = $foreignKey->reference->key;
    $this->joinTable = $foreignKey->reference->entity->table;

    unset( $this->foreignKey );
  }
}