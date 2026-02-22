<?php

namespace Websyspro\SqlFromClass\Enums;

/**
 * Enum que define a prioridade de uma entidade na consulta SQL
 * 
 * Determina se uma entidade é primária (primeiro parâmetro da função)
 * ou secundária (parâmetros subsequentes)
 */
enum EntityPriority
{
  /** Entidade primária - corresponde ao primeiro parâmetro da função */
  case Primary;
  
  /** Entidade secundária - corresponde aos demais parâmetros */
  case Secundary;
}