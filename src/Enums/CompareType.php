<?php

namespace Websyspro\SqlFromClass\Enums;

/**
 * Enum que define os tipos de operadores de comparação SQL
 * 
 * Representa os operadores utilizados em cláusulas WHERE para comparar valores
 */
enum CompareType:string
{
  /** Operador de igualdade (=) */
  case EQUALS = '=';
  
  /** Operador maior que (>) */
  case GREATER = '>';
  
  /** Operador menor que (<) */
  case LESS = '<';
  
  /** Operador maior ou igual (>=) */
  case GREATER_EQUAL = '>=';
  
  /** Operador menor ou igual (<=) */
  case LESS_EQUAL = '<=';
  
  /** Operador diferente (<>) */
  case NOT_EQUAL = '<>';
}