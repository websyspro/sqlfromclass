<?php

namespace Websyspro\SqlFromClass\Enums;

/**
 * Enum que define os tipos de tokens identificados no corpo de uma arrow function
 * 
 * Cada tipo representa um elemento diferente da expressão que será convertida em SQL
 */
enum TokenType
{
  /** Campo de uma entidade (ex: $user->name) */
  case FieldEntity;
  
  /** Variável estática capturada pela closure (ex: $variable) */
  case FieldStatic;
  
  /** Valor literal (ex: 'texto', 123) */
  case FieldValue;
  
  /** Referência a um enum (ex: Status::Active) */
  case FieldEnum;
  
  /** Operador de comparação (ex: =, >, <) */
  case Compare;
  
  /** Operador lógico (ex: &&, ||, and, or) */
  case Logical;
  
  /** Parêntese de abertura */
  case StartParent;
  
  /** Parêntese de fechamento */
  case EndParent;
  
  /** Token vazio */
  case Empty;
  
  /** Token a ser ignorado no processamento */
  case FieldIgnore;
}