<?php

namespace Websyspro\SqlFromClass;

use ReflectionNamedType;
use Websyspro\SqlFromClass\Enums\TokenType;
use Websyspro\Commons\Collection;
use Websyspro\Commons\Util;
use ReflectionParameter;
use ReflectionFunction;

/**
 * Classe utilitária para conversão de arrow functions em estruturas SQL
 * 
 * Responsável por extrair e processar informações de arrow functions,
 * incluindo parâmetros, variáveis estáticas, imports e tokens do corpo da função
 */
class Shareds
{
  /**
   * Extrai parâmetros de uma arrow function
   * 
   * @param ReflectionFunction $reflectionFunction Função refletida
   * @return Collection Coleção de objetos Parameter
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
          $reflectionParameterType instanceof ReflectionNamedType ? call_user_func_array(
            [ $reflectionParameterType->getName(), "getAttributes" ], []
          ) : null
        );
      }
    );
  }

  /**
   * Extrai variáveis estáticas capturadas pela arrow function
   * 
   * @param ReflectionFunction $reflectionFunction Função refletida
   * @return Collection Coleção de variáveis estáticas
   */
  private static function createStaticFromArrowFn(
    ReflectionFunction $reflectionFunction
  ): Collection {
    return new Collection(
      $reflectionFunction->getStaticVariables()
    );
  }

  /**
   * Extrai declarações de use do arquivo onde a função está definida
   * 
   * @param ReflectionFunction $reflectionFunction Função refletida
   * @return Collection Coleção de objetos UseClass
   */
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
   * Inicializa coleção vazia de JOINs
   * 
   * @param ReflectionFunction $reflectionFunction Função refletida
   * @return Collection Coleção vazia de JOINs
   */
  private static function createJoinsFromArrowFn(
    ReflectionFunction $reflectionFunction
  ): Collection {
    return new Collection();
  }  

  /**
   * Converte uma string token em seu tipo TokenType correspondente
   * 
   * @param string $token String do token a ser classificado
   * @return TokenType Tipo do token identificado
   */
  private static function convertToken(
    string $token
  ): TokenType {
    if( Util::match("#(=|==|===|<>|!=|!==|>=|<=)#", $token)){
      return TokenType::Compare;
    } else
    if( Util::match("#^\\\$.*->.*$#", $token)){
      return TokenType::FieldEntity;
    } else
    if( Util::match("#\\\$(\{[a-zA-Z_][a-zA-Z0-9_]*\}|[a-zA-Z_][a-zA-Z0-9_]*)#", $token)){
      return TokenType::FieldStatic;
    } else
    if( Util::match("#^(\\\"|').*(\\\"|')$#", $token)){
      return TokenType::FieldValue;
    } else
    if( Util::match( "#(&&|\|\||And|Or)#", $token)){
      return TokenType::Logical;
    }else
    if( Util::match( "#^[a-zA-Z]{1}.*::.*(->(?:name|value))?$#", $token )){
      return TokenType::FieldEnum;
    } else
    if(Util::match("#^,$#", $token)){
      return TokenType::FieldIgnore;
    } else
    if( Util::match( "#^\($#", $token )){
      return TokenType::StartParent;
    } else 
    if( Util::match( "#^\)$#", $token )){
      return TokenType::EndParent;
    }

    return TokenType::FieldValue;
  }

  /**
   * Cria um objeto Token a partir de uma string
   * 
   * @param string $token String do token
   * @return Token Objeto Token criado
   */
  private static function tokenParse(
    string $token
  ): Token {
    return new Token(
      Shareds::convertToken( $token ),
      $token
    );
  }

  /**
   * Extrai e tokeniza o corpo de uma arrow function
   * 
   * Lê o código fonte da função, normaliza e divide em tokens
   * 
   * @param ReflectionFunction $reflectionFunction Função refletida
   * @return Collection Coleção de objetos Token
   */
  private static function createBodyFromArrowFn(
    ReflectionFunction $reflectionFunction
  ): Collection {
    $sourceArrowFN = new Collection(
      file( $reflectionFunction->getFileName())
    );

    // Normaliza o código fonte removendo formatação e convertendo operadores
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
        "",     // Remove carriage return
        " ",    // Remove quebras de linha
        "",     // Remove abertura de função
        "",     // Remove fechamento de função
        "fn(",  // Normaliza declaração de função
        "",     // Remove fechamento de parênteses
        "",     // Remove arrow function
        "(",    // Converte colchetes em parênteses
        ")",    // Converte colchetes em parênteses
        ",",    // Normaliza vírgulas
        "'",    // Converte aspas duplas em simples
        "And",  // Converte && em And
        "Or",   // Converte || em Or
        "<>",   // Normaliza operador diferente
        "=",    // Normaliza operador igual
        "1",    // Converte true em 1
        "0"     // Converte false em 0
      ],  
      $sourceArrowFN->slice(
        $reflectionFunction->getStartLine() - 1,
        $reflectionFunction->getEndLine() - $reflectionFunction->getStartLine() + 1
      )->toString()
    );

    // Extrai tokens usando regex
    preg_match_all(
        "#'[^']*'|\"[^\"]*\"|\\$?[\\w\\\\-]+(?:->|::)[\\w\\\\-]+|\\$?[\\w\\\\-]+|>=|<=|<>|[<>=!]+|\\(|\\)|,|\\S#",
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
   * Converte uma arrow function em objeto ArrowFnToSql
   * 
   * Método principal que orquestra a extração de todas as informações da função
   * 
   * @param callable $arrowFnToString Arrow function a ser processada
   * @return ArrowFnToSql Objeto contendo toda a estrutura da função
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
      Shareds::createJoinsFromArrowFn( $reflectionFunction ),
      Shareds::createUsesFromArrowFn( $reflectionFunction ),
      Shareds::createBodyFromArrowFn( $reflectionFunction )
    );
  }
}