<?php

namespace Websyspro\SqlFromClass\Enums;

/**
 * Enum que define os operadores lógicos SQL
 * 
 * Representa os operadores utilizados para combinar condições em cláusulas WHERE
 */
enum LogicalType: string
{
  /** Operador lógico AND - todas as condições devem ser verdadeiras */
  case And = "and";
  
  /** Operador lógico OR - pelo menos uma condição deve ser verdadeira */
  case Or = 'or';
}