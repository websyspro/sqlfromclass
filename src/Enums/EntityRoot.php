<?php

namespace Websyspro\SqlFromClass\Enums;

/**
 * Enum que indica se uma entidade é raiz na hierarquia de joins
 * 
 * Uma entidade é considerada raiz quando não possui relacionamentos oneToMany
 * em seu histórico de hierarquia
 */
enum EntityRoot {
  /** Entidade é raiz - pode ser usada como base para a consulta */
  case Yes;
  
  /** Entidade não é raiz - possui relacionamentos oneToMany no histórico */
  case No;
}