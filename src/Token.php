<?php

namespace Websyspro\SqlFromClass;

use Websyspro\Commons\Collection;
use Websyspro\SqlFromClass\Enums\EntityPriority;
use Websyspro\SqlFromClass\Enums\EntityRoot;
use Websyspro\SqlFromClass\Enums\TokenType;

/**
 * Representa um token identificado no corpo de uma arrow function
 * 
 * Armazena informações sobre um elemento da expressão que será convertida em SQL,
 * incluindo seu tipo, valor e metadados da entidade associada
 */
class Token
{
  /**
   * @param TokenType $takenType Tipo do token (FieldEntity, Compare, Logical, etc)
   * @param string|Collection $value Valor do token
   * @param string|null $entity Classe da entidade associada ao token
   * @param string|null $entityName Nome legível da entidade
   * @param string|null $entityField Nome do campo da entidade
   * @param EntityRoot|null $entityRoot Indica se a entidade é raiz na hierarquia
   * @param EntityPriority|null $entityPriority Prioridade da entidade (Primary/Secundary)
   */
  public function __construct(
    public TokenType $takenType,
    public string|Collection $value,
    public string|null $entity = null,
    public string|null $entityName = null,
    public string|null $entityField = null,
    public EntityRoot|null $entityRoot = null,
    public EntityPriority|null $entityPriority = null
  ){}
}