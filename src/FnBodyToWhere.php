<?php

namespace Websyspro\SqlFromClass;

use BackedEnum;
use ReflectionFunction;
use UnitEnum;
use Websyspro\Commons\Collection;
use Websyspro\Commons\Util;
use Websyspro\SqlFromClass\Enums\EntityPriority;
use Websyspro\SqlFromClass\Enums\Token;

/**
 * Converts function body tokens to WHERE clause conditions by analyzing
 * field entities and determining their relationships to function parameters
 */
class FnBodyToWhere
{
  /**
   * Class constructor that initializes the function body to WHERE clause converter
   * @param ReflectionFunction $reflectionFunction The reflected function to analyze
   * @param Collection $paramters Collection of function parameters
   * @param Collection $static Collection of static values
   * @param Collection $body Collection of body tokens to process
   */
  public function __construct(
    public ReflectionFunction $reflectionFunction,
    public Collection $paramters,
    public Collection $statics,
    public Collection $uses,
    public Collection $body
  ){
    /* Process entity definitions and priorities */
    $this->defineEntityAndPriority();
    $this->defineFieldEntity();
    $this->defineFieldEnums();
    $this->defineFieldStatics();
    
    //print_r($this->body->mapper(fn(TokenList $tokenList) => $tokenList->value)->joinWithSpace());
    
    //print_r( $this->body );
  }

  private function useClass(
    string $class
  ): UseClass {
    if( Util::match( "#^.*\\\.*$#", $class )){
      $classPaths = new Collection(
        preg_split(
          "#\\\#",
          $class, 
          -1, 
          PREG_SPLIT_NO_EMPTY
        )
      );

      $class = $classPaths->last();
    }

    return $this->uses->find(
      fn(UseClass $useClass) => $useClass->isClass($class) 
    );
  }

  /**
   * Determines the priority level of an entity based on function parameters
   * @param string $entity The entity to check priority for
   * @return EntityPriority Returns Primary if entity matches first parameter, otherwise Secondary
   */
  private function entityPrimary(
    string $entity
  ): EntityPriority {
    /* Return secondary priority if no parameters exist */
    if($this->paramters->exist() === false){
      return EntityPriority::Secundary;
    }

    /* Get the first parameter from the collection */
    $parameterFirst = $this->paramters->first();
    if($parameterFirst instanceof FnParameter){
      /* Compare entity with first parameter's entity to determine priority */
      return $parameterFirst->entity === $entity 
        ? EntityPriority::Primary 
        : EntityPriority::Secundary;
    }

    /* Default to secondary priority if parameter validation fails */
    return EntityPriority::Secundary;
  }

  /**
   * Converts entity class name to a readable name format
   * @param string $entity The entity class name to convert
   * @return bool The converted entity name
   */
  private function entityName(
    string $entity
  ): string {
    /* Convert class name to readable name format using utility function */
    return Util::classToName( $entity );
  }  
 
  /**
   * Maps through the body tokens to identify and assign entity information
   * to field entity tokens, determining their corresponding entity name
   * and whether they represent primary entities based on function parameters
   */
  private function defineEntityAndPriority(
  ): void {
    /* Map through each token in the body collection */
    $this->body = $this->body->mapper(
      function(TokenList $tokenList) {
        /* Check if current token is a field entity type */
        if( $tokenList->taken === Token::FieldEntity ) {
          /* Extract parameter name by removing $ prefix and -> suffix, then find matching entity */
          $tokenList->entity = $this->paramters->find( fn( FnParameter $fnParameter ) => (
            $fnParameter->name === preg_replace( [ "#^\\$#", "#->.*$#" ], "", $tokenList->value )
          ))->entity;

          /* Convert entity class to readable name format */
          $tokenList->entityName = $this->entityName( $tokenList->entity );
          /* Check if this entity is marked as primary */
          $tokenList->entityPriority = $this->entityPrimary( $tokenList->entity );
        }

        /* return tokenList */
        return $tokenList;
      }
    );
  }

  /**
   * Transforms field entity tokens by replacing parameter references with entity names
   * Converts tokens like "$parameter->field" to "EntityName.field" format
   */
  private function defineFieldEntity(
  ): void {
    /* Map through body tokens to transform field entity references */
    $this->body = $this->body->mapper(
      function( TokenList $tokenList ) {
        /* Process only field entity tokens */
        if( $tokenList->taken === Token::FieldEntity ){
          /* Replace parameter reference with entity name using regex pattern */
          $tokenList->value = preg_replace(
            "#^\\$.*->#",
            "{$tokenList->entityName}.", 
            $tokenList->value
          );
        }

        /* Return the processed token */
        return $tokenList;
      }
    );
  }

  /**
   * Processa tokens de enum values, convertendo referências de enum para seus valores
   * Transforma tokens como "EnumClass::VALUE" para o nome do valor do enum
   */
  private function defineFieldEnums(
  ): void {
    $this->body = $this->body->mapper(
      function( TokenList $tokenList ) {
        /* Processa apenas tokens de enum value */
        if( $tokenList->taken === Token::EnumValue ){
          /* Divide a string do enum em classe e item usando :: como separador */
          if( preg_match( "#->#", $tokenList->value ) === 0){
            [ $unitEnum, $unitEnumItem ] = preg_split( 
              "#(::)#", $tokenList->value, -1, PREG_SPLIT_NO_EMPTY 
            );
          } else {
            [ $unitEnum, $unitEnumItem, $unitEnumMethod ] = preg_split( 
              "#(::)|(->)#", $tokenList->value, -1, PREG_SPLIT_NO_EMPTY 
            );
          }

          /* Encontra a classe de use correspondente ao enum */
          $useClass = $this->useClass( $unitEnum );
          /* Obtém a constante do enum usando o namespace completo */
          $unitEnum = constant( $useClass->fullClassFromUnitEnum( $unitEnumItem ));
          
          /* Se for uma instância válida de UnitEnum, substitui pelo nome do valor */
          if( $unitEnum instanceof UnitEnum ){
            if( $unitEnum instanceof BackedEnum ){
              if( $unitEnumMethod !== null ){
                $tokenList->value = $unitEnum->{$unitEnumMethod};
              }
            } else {
              $tokenList->value = $unitEnum->name;
            }
          }
        }

        return $tokenList;
      }
    );    
  }

  public function parseFromStatics(
    string $value
  ): string {
    var_dump($value);

    $this->statics->mapper(
      function(
        string $staticValue,
        string $staticKey
      ) use(&$value){
        $value = preg_replace(
          "#\\\${$staticKey}#",
          $staticValue,
          $value
        );
      }
    );

    var_dump($value);
    return $value;
  }

  public function defineFieldStatics(
  ): void {
    $this->body = $this->body->mapper(
      function( TokenList $tokenList ) {
        if( $tokenList->taken === Token::FieldStatic ){
          // $tokenList->value = $this->parseFromStatics( 
          //   $tokenList->value
          // );

          print_r($tokenList);
        }

        return $tokenList;
      }
    );
  }
}