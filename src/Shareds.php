<?php

namespace Websyspro\SqlFromClass;

use Websyspro\SqlFromClass\Enums\TokenType;
use Websyspro\Commons\Collection;
use Websyspro\Commons\Util;
use ReflectionParameter;
use ReflectionFunction;

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
      function( ReflectionParameter $reflectionParameter ) {
        $reflectionParameterType = $reflectionParameter->getType();

        return new Parameter(
          $reflectionParameter->getName(),
          $reflectionParameterType,
          "{$reflectionParameterType}::getColumns"()
        );
      }
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
  ): TokenType {
    if(Util::match("#(=|==|===|<>|!=|!==|>=|<=)#", $token)){
      return TokenType::Compare;
    } else
    if(Util::match("#^\\\$.*->.*$#", $token)){
      return TokenType::FieldEntity;
    } else
    if(Util::match("#\\\$(\{[a-zA-Z_][a-zA-Z0-9_]*\}|[a-zA-Z_][a-zA-Z0-9_]*)#", $token)){
      return TokenType::FieldStatic;
    } else
    if(Util::match("#^(\\\"|').*(\\\"|')$#", $token)){
      return TokenType::FieldValue;
    } else
    if(Util::match( "#(&&|\|\||And|Or)#", $token)){
      return TokenType::Logical;
    }else
    if(Util::match( "#^[a-zA-Z]{1}.*::.*(->(?:name|value))?$#", $token )){
      return TokenType::EnumValue;
    } else
    if(Util::match( "#^\($#", $token )){
      return TokenType::StartParent;
    } else 
    if(Util::match( "#^\)$#", $token )){
      return TokenType::EndParent;
    }

    return TokenType::FieldValue;
  }

  private static function tokenParse(
    string $token
  ): Token {
    return new Token(
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

    preg_match_all(
       "#'[^']*'|\"[^\"]*\"|\\S+#",
      $sourceString,
      $sourceCollectionTokens
    );

    if( Util::sizeArray($sourceCollectionTokens) === 1 ){
      [ $sourceCollections ] = $sourceCollectionTokens;

      $sourceCollection = new Collection( 
        $sourceCollections
      );

      return $sourceCollection->mapper(
        fn( string $token): Token => Shareds::tokenParse( $token )
      );      
    }
  
    return new Collection();
  }

  /**
   * Converte uma arrow function em objeto FnBody
   */
  public static function createTokens(
    callable $arrowFnToString
  ): ArrowFnToSql {
    $reflectionFunction = new ReflectionFunction(
      $arrowFnToString
    );

    return new ArrowFnToSql(
      $reflectionFunction,
      Shareds::createParametersFromArrowFn( $reflectionFunction ),
      Shareds::createStaticFromArrowFn( $reflectionFunction ),
      Shareds::createUsesFromArrowFn( $reflectionFunction ),
      Shareds::createBodyFromArrowFn( $reflectionFunction )
    );
  }
}