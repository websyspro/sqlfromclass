<?php

namespace Websyspro\SqlFromClass;

use ReflectionFunction;
use Websyspro\Commons\Collection;

class Shareds
{
  public static function arrowFnToString(
    callable $arrowFnToString
  ): FnBody {
    $reflectionFunction = new ReflectionFunction(
      $arrowFnToString
    );

    $sourceArrowFN = new Collection(
      file( $reflectionFunction->getFileName())
    );

    $sourceArrowString = $sourceArrowFN->slice(
      $reflectionFunction->getStartLine() - 1,
      $reflectionFunction->getEndLine() - $reflectionFunction->getStartLine() + 1
    )->toString();

    return new FnBody(
      $reflectionFunction,
      [],
      preg_replace(
        [ "#^.*(fn|function)\s*\(#", "#\s*\);\s*$#" ], 
        [ "fn(", "" ],  
        $sourceArrowString
      )
    );
  }
}