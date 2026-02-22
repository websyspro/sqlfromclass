<?php

namespace Websyspro\SqlFromClass\Interfaces;

/**
 * Representa uma cláusula FROM na estrutura SQL
 * 
 * Armazena informações sobre uma entidade na cláusula FROM e se deve usar INNER JOIN
 */
class Froms
{
  /**
   * @param string $entity Nome da entidade
   * @param bool $innerJoin Indica se deve usar INNER JOIN (true) ou LEFT JOIN (false)
   */
  public function __construct(
    public string $entity,
    public bool $innerJoin = false
  ){}
}