<?php

namespace Websyspro\SqlFromClass\Interfaces;

use Websyspro\Commons\Collection;

/**
 * Representa a estrutura completa de uma consulta SQL
 * 
 * Contém todas as partes de uma query SQL: colunas, tabelas (FROM),
 * condições (WHERE) e parâmetros
 */
class StrutureSql
{
  /**
   * @param Collection|null $columns Coleção de colunas (SELECT)
   * @param Collection|null $froms Coleção de tabelas e JOINs (FROM)
   * @param Collection|null $wheres Coleção de condições (WHERE)
   * @param Collection|null $params Coleção de parâmetros da query
   */
  public function __construct(
    public Collection|null $columns = null,
    public Collection|null $froms = null,
    public Collection|null $wheres = null,
    public Collection|null $params = null,
  ){}
}