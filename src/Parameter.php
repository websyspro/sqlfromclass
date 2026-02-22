<?php

namespace Websyspro\SqlFromClass;

use Websyspro\Entity\Shareds\EntityStructure;

/**
 * Representa um parâmetro de função com sua entidade associada
 * 
 * Armazena informações sobre um parâmetro de arrow function,
 * incluindo seu nome, tipo de entidade e estrutura da entidade
 */
class Parameter
{
  /**
   * @param string $name Nome do parâmetro
   * @param string $entity Classe da entidade associada ao parâmetro
   * @param EntityStructure|null $entityStructure Estrutura completa da entidade (colunas, relacionamentos, etc)
   */
  public function __construct(
    public string $name,
    public string $entity,
    public EntityStructure|null $entityStructure = null
  ){}   
}