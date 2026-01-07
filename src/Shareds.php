<?php

namespace Websyspro\SqlFromClass;

use ReflectionFunction;
use ReflectionParameter;
use Websyspro\Commons\Collection;

class Shareds
{
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

  public static function arrowFnToString(
    callable $arrowFnToString
  ): FnBody {
    $reflectionFunction = new ReflectionFunction(
      $arrowFnToString
    );

    return new FnBody(
      $reflectionFunction,
      Shareds::createParametersFromArrowFn( $reflectionFunction ),
      Shareds::createBodyFromArrowFn( $reflectionFunction )
    );
  }
}