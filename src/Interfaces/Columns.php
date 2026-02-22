<?php

namespace Websyspro\SqlFromClass\Interfaces;

/**
 * Representa uma coluna na estrutura SQL
 * 
 * Armazena informações sobre uma coluna específica e sua entidade associada
 */
class Columns
{
  /**
   * @param string $name Nome da coluna
   * @param string $entity Nome da entidade à qual a coluna pertence
   */
  public function __construct(
    public string $name,
    public string $entity
  ){}
}