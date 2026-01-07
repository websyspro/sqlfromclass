<?php

namespace Websyspro\SqlFromClass;

use ReflectionFunction;
use ReflectionParameter;
use Websyspro\Commons\Collection;
use Websyspro\Commons\Util;
use Websyspro\SqlFromClass\Enums\Token;

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

  private static function toToken(
    string $token
  ): Token {
    if(Util::match("#(=|==|===)#", $token)){
      return Token::Compare;
    } else
    if(Util::match("#^\\$.*->.*$#", $token)){
      return Token::FieldEntity;
    } else
    if(Util::match("#^\\$.*$#", $token)){
      return Token::FieldStatic;
    } else
    if(Util::match("#^\".*\"$#", $token)){
      return Token::FieldValue;
    } else
    if(Util::match( "#(&&|\|\||and|or)#", $token)){
      return Token::Logical;
    } else
    if(Util::match( "#\(#", $token )){
      return Token::StartParent;
    } else 
    if(Util::match( "#\)#", $token )){
      return Token::EndParent;
    }

    return Token::Empty;
  }

  private static function tokenParse(
    string $token
  ): TokenList {
    return new TokenList(
      Shareds::toToken( $token ), $token
    );
  }

  /**
   * Extrai o corpo de uma arrow function do código fonte
   */
  private static function createBodyFromArrowFn(
    ReflectionFunction $reflectionFunction
  ): Collection {
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

    $sourceCollection = new Collection(
      preg_split( "#\s#",  $sourceString)
    );
    
    return $sourceCollection->mapper(
      fn( string $token): TokenList => Shareds::tokenParse( $token )
    );
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