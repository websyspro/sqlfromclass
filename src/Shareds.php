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

  private static function createUsesFromArrowFn(
    ReflectionFunction $reflectionFunction
  ): Collection {
    $fileLines = new Collection(
      file( $reflectionFunction->getFileName())
    );

    $fileUses = $fileLines->where(
      fn(string $text) => preg_match(
        "#^use\s*#", $text
      ) === 1
    );

    return $fileUses->mapper( 
      fn(string $useClass) => (
        new UseClass($useClass)
      )
    );
  }

  /**
   * Converts a string token into its corresponding Token enum type
   */
  private static function convertToken(
    string $token
  ): Token {
    if(Util::match("#(=|==|===|<>|!=|!==|>=|<=)#", $token)){
      return Token::Compare;
    } else
    if(Util::match("#^\\\$.*->.*$#", $token)){
      return Token::FieldEntity;
    } else
    if(Util::match("#\\\$(\{[a-zA-Z_][a-zA-Z0-9_]*\}|[a-zA-Z_][a-zA-Z0-9_]*)#", $token)){
      return Token::FieldStatic;
    } else
    if(Util::match("#^(\"|').*(\"|')$#", $token)){
      return Token::FieldValue;
    } else
    if(Util::match( "#(&&|\|\||And|Or)#", $token)){
      return Token::Logical;
    }else
    if(Util::match( "#^[a-zA-Z]{1}.*::.*(->(?:name|value))?$#", $token )){
      return Token::EnumValue;
    } else
    if(Util::match( "#^\($#", $token )){
      return Token::StartParent;
    } else 
    if(Util::match( "#^\)$#", $token )){
      return Token::EndParent;
    }

    return Token::FieldValue;
  }

  private static function tokenParse(
    string $token
  ): TokenList {
    return new TokenList(
      Shareds::convertToken( $token ),
      $token
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
      [
        "#\r#",
        "#\n\s*#",
        "#^.*\\{.*return\s*#",
        "#\s*;\s*\\}\s*#",
        "#^.*(fn|function)\s*\(#",
        "#\s*\);\s*$#",
        "#^.*?\)\s*=>\s*#s",
        "#\\[\s*#s",
        "#\s*\\]#s",
        "#,\s*#s",
        "#\"#s",
        "#&&#",
        "#\|\|#",
        "#(!==|!=)#",
        "#(===|==|=)#",
        "#true#",
        "#false#"
      ], 
      [
        "",     // "#\r#"
        " ",    // "#\n\s*#"
        "",     // "#^.*\\{.*return\s*#"
        "",     // "#\s*;\s*\\}\s*#"
        "fn(",  // "#^.*(fn|function)\s*\(#"
        "",     // "#\s*\);\s*$#"
        "",     // "#^.*?\)\s*=>\s*#s"
        "(",    // "#\\[\s*#"
        ")",    // "#\s*\\]#"
        ",",    // "#,\s*#s"]
        "'",    // "#\"#"
        "And",  // "#&&#"
        "Or",   // "#\|\|#"
        "<>",   // "#(!==|!=)#" 
        "=",    // "#^(===|==|=)$#",
        "1",    // "#true#"
        "0"     // "#false#" 
      ],  
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
  public static function arrowFnToTokens(
    callable $arrowFnToString
  ): FnBodyToWhere {
    $reflectionFunction = new ReflectionFunction(
      $arrowFnToString
    );

    return new FnBodyToWhere(
      $reflectionFunction,
      Shareds::createParametersFromArrowFn( $reflectionFunction ),
      Shareds::createStaticFromArrowFn( $reflectionFunction ),
      Shareds::createUsesFromArrowFn( $reflectionFunction ),
      Shareds::createBodyFromArrowFn( $reflectionFunction )
    );
  }
}