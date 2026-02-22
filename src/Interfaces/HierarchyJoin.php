<?php

namespace Websyspro\SqlFromClass\Interfaces;

use Websyspro\SqlFromClass\Enums\EntityRoot;
use Websyspro\Entity\Shareds\Entity;

/**
 * Representa a hierarquia de JOINs entre entidades
 * 
 * Armazena informações sobre a posição de uma entidade na hierarquia de relacionamentos,
 * incluindo seu histórico, entidade pai e informações de JOIN
 */
class HierarchyJoin {
  /**
   * @param Entity $entity Nome da entidade atual
   * @param array $entityHistory Histórico de tipos de relacionamento (oneToOne, oneToMany)
   * @param string|null $entityParent Nome da entidade pai na hierarquia
   * @param EntityRoot $entityRoot Indica se a entidade é raiz na hierarquia
   * @param EntityJoin|null $entityJoin Informações do JOIN com a entidade pai
   */
  public function __construct(
    public Entity $entity,
    public array $entityHistory = [],
    public string|null $entityParent = null,
    public EntityRoot $entityRoot = EntityRoot::Yes,
    public EntityJoin|null $entityJoin = null
  ){}
}