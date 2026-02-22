<?php

namespace Websyspro\SqlFromClass\Interfaces;

use Websyspro\Entity\Shareds\ForeignKey;

/**
 * Representa um JOIN entre entidades no SQL
 * 
 * Armazena as informações necessárias para criar um JOIN entre duas tabelas
 * baseado em uma chave estrangeira
 */
class EntityJoin
{
  /** @var string Nome da tabela origem */
  public string $table;
  
  /** @var string Nome da coluna chave na tabela origem */
  public string $key;
  
  /** @var string Nome da tabela destino do JOIN */
  public string $joinTable;
  
  /** @var string Nome da coluna chave na tabela destino */
  public string $joinKey;   

  /**
   * @param ForeignKey|null $foreignKey Objeto contendo as informações da chave estrangeira
   */
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