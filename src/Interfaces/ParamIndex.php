<?php

namespace Websyspro\SqlFromClass\Interfaces;

/**
 * Representa um parâmetro indexado na consulta SQL
 * 
 * Armazena o índice e valor de um parâmetro, gerando um alias único
 * para substituição na query SQL final
 */
class ParamIndex
{
  /**
   * @param int $index Índice do parâmetro na sequência
   * @param string $value Valor do parâmetro
   */
  public function __construct(
    public int $index,
    public string $value,
  ){}

  /**
   * Gera um alias único para o parâmetro
   * 
   * @return string Alias no formato "$[Param_{index}]"
   */
  public function getAlias(
  ): string {
    return "$[Param_{$this->index}]";
  }
}