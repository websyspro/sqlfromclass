<?php

namespace Websyspro\SqlFromClass;

use ReflectionFunction;
use ReflectionParameter;
use Websyspro\Commons\Collection;

/**
 * Classe utilitária para conversão de arrow functions em objetos FnBody
 */
class Shareds
{
  /**
   * Extrai parâmetros de uma arrow function
   */
  private static function createParametersFromArrowFn(
    ReflectionFunction $reflectionFunction
  ): Collection {
    $parameters = new Collection(
      $reflectionFunction->getParameters()
    );

    return $parameters->mapper(
      fn( ReflectionParameter $reflectionParameter ) => new FnParameter(
        $reflectionParameter->getName(),
        $reflectionParameter->getType()
      )
    );
  }

  /**
   * Extrai variáveis estáticas de uma arrow function
   */
  private static function createStaticFromArrowFn(
    ReflectionFunction $reflectionFunction
  ): Collection {
    return new Collection(
      $reflectionFunction->getStaticVariables()
    );
  }

  /**
   * Extrai o corpo de uma arrow function do código fonte
   */
  private static function createBodyFromArrowFn(
    ReflectionFunction $reflectionFunction
  ): string {
    $sourceArrowFN = new Collection(
      file( $reflectionFunction->getFileName())
    );

    $sourceString = preg_replace(
      [ "#^.*(fn|function)\s*\(#", "#\s*\);\s*$#", "#^.*?\)\s*=>\s*#s", "#\r#", "#\n\s*#" ], 
      [ "fn(", "", "", "", " " ],  
      $sourceArrowFN->slice(
        $reflectionFunction->getStartLine() - 1,
        $reflectionFunction->getEndLine() - $reflectionFunction->getStartLine() + 1
      )->toString()
    );
    
    return trim($sourceString);
  }

  /**
   * Converte uma arrow function em objeto FnBody
   */
  public static function arrowFnToString(
    callable $arrowFnToString
  ): FnBody {
    $reflectionFunction = new ReflectionFunction(
      $arrowFnToString
    );

    return new FnBody(
      $reflectionFunction,
      Shareds::createParametersFromArrowFn( $reflectionFunction ),
      Shareds::createStaticFromArrowFn( $reflectionFunction ),
      Shareds::createBodyFromArrowFn( $reflectionFunction )
    );
  }
}