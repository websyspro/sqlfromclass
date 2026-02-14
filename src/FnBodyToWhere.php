<?php

namespace Websyspro\SqlFromClass;

use Websyspro\SqlFromClass\Enums\EntityPriority;
use Websyspro\SqlFromClass\Enums\Token;
use Websyspro\Commons\Collection;
use Websyspro\Commons\Util;
use ReflectionFunction;
use BackedEnum;
use UnitEnum;

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
    $this->defineFieldValue();
    $this->defineFieldEnums();
    $this->defineFieldStatics();
    $this->defineFieldSides();
    $this->defineFieldComparesGroup();
    
    print_r($this->body->mapper(fn(TokenList $tokenList) => $tokenList->value)->joinWithSpace());
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

          $tokenListCollection =  new Collection(
            preg_split( 
              "#\.#", 
              $tokenList->value,
              -1, 
              PREG_SPLIT_NO_EMPTY
            )
          );

          $tokenList->entityField = $tokenListCollection->last();
        }

        /* Return the processed token */
        return $tokenList;
      }
    );
  }

  /**
   * Checks if a token is a field entity type
   * @param TokenList|null $tokenList The token to check
   * @return bool Returns true if token is a FieldEntity, false otherwise
   */
  private function fieldIsEntity(
    TokenList|null $tokenList
  ): bool {
    /* Check if token exists and is a FieldEntity type */
    return isset( $tokenList ) && $tokenList->taken === Token::FieldEntity;
  }  

  /**
   * Checks if a token is a comparison operator
   * @param TokenList|null $tokenList The token to check
   * @return bool Returns true if token is a Compare operator, false otherwise
   */
  private function fieldIsCompare(
    TokenList|null $tokenList
  ): bool {
    /* Check if token exists and is a Compare operator type */
    return isset( $tokenList ) && $tokenList->taken === Token::Compare;
  }

  /**
   * Inverts comparison operators for reversed field comparisons
   * @param TokenList $tokenList The token containing the comparison operator
   * @return TokenList Returns the token with inverted operator (>= becomes <=, > becomes <, etc)
   */
  private function fieldCompareInvert(
    TokenList $tokenList
  ): TokenList {
    /* Invert comparison operator using match expression */
    $tokenList->value = match( $tokenList->value ){
      ">=" => "<=", "<=" => ">=", ">" => "<", "<" => ">",
      default => $tokenList->value
    };

    return $tokenList;
  }

  /**
   * Checks if a token is a field value type
   * @param TokenList|null $tokenList The token to check
   * @return bool Returns true if token is a FieldValue, false otherwise
   */
  private function fieldIsValue(
    TokenList|null $tokenList
  ): bool {
    /* Check if token exists and is a FieldValue type */
    return isset( $tokenList ) && $tokenList->taken === Token::FieldValue;
  }  

  /**
   * Assigns entity metadata to field value tokens based on adjacent comparison tokens
   * Copies entity information from the related field entity to the value token
   */
  private function defineFieldValue(
  ): void {
    /* Map through body tokens to assign entity metadata to field values */
    $this->body = $this->body->mapper(
      function( TokenList $tokenList, int $i ) {
        /* Process only field value tokens */
        if( $tokenList->taken === Token::FieldValue ){
          /* Check if previous token exists (Value after Entity pattern) */
          if( $this->body->eq( $i - 1 )->exist() ){
            $tokenListPrev = $this->body->eq( $i - 1 )->first();
            /* Verify previous token is a comparison operator */
            if( $tokenListPrev->taken === Token::Compare ){
              $tokenListComparePrev = $this->body->eq( $i - 2 )->first();

              /* Copy entity metadata from the field entity to this value */
              if( $tokenListComparePrev instanceof TokenList ){
                $tokenList->entity = $tokenListComparePrev->entity;
                $tokenList->entityName = $tokenListComparePrev->entityName;
                $tokenList->entityField = $tokenListComparePrev->entityField; 
                $tokenList->entityPriority = $tokenListComparePrev->entityPriority;
              }
            }
          } else
          /* Check if next token exists (Value before Entity pattern) */
          if( $this->body->eq( $i + 1 )->exist() ){
            $tokenListNext = $this->body->eq( $i + 1 )->first();
            /* Verify next token is a comparison operator */
            if( $tokenListNext->taken === Token::Compare ){
              $tokenListCompareNext = $this->body->eq( $i + 2 )->first();

              /* Copy entity metadata from the field entity to this value */
              if( $tokenListCompareNext instanceof TokenList ){
                $tokenList->entity = $tokenListCompareNext->entity;
                $tokenList->entityName = $tokenListCompareNext->entityName;
                $tokenList->entityField = $tokenListCompareNext->entityField;
                $tokenList->entityPriority = $tokenListCompareNext->entityPriority;
              }
            }
          }
        }

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

  /**
   * Parses static variable references in field values
   * @param string $value The value containing static variable references
   * @return string Returns the value with static variables replaced by their actual values
   */
  public function parseFromStatics(
    string $value
  ): string {
    /* Map through static variables collection to replace references */
    $this->statics->mapper(
      function(
        string $staticValue,
        string $staticKey
      ) use(&$value){
        /* Replace static variable reference with its actual value */
        $value = preg_replace(
          "#\\\${$staticKey}#",
          $staticValue,
          $value
        );
      }
    );

    return $value;
  }

  /**
   * Processes field static tokens by replacing static variable references with their values
   * Transforms tokens containing static variables into their resolved string values
   */
  public function defineFieldStatics(
  ): void {
    /* Map through body tokens to process static field values */
    $this->body = $this->body->mapper(
      function( TokenList $tokenList ) {
        /* Process only field static tokens */
        if( $tokenList->taken === Token::FieldStatic ){
          /* Parse and replace static variable references */
          $tokenList->value = $this->parseFromStatics( 
            $tokenList->value
          );
        }

        return $tokenList;
      }
    );
  }

  /**
   * Normalizes comparison order by ensuring field entities are always on the left side
   * Transforms "Value Compare Entity" patterns to "Entity Compare Value" and inverts operators
   */
  public function defineFieldSides(
  ): void {
    /* Get all items from body collection */
    $items = $this->body->all();
    
    /* Iterate through items to find reversed comparison patterns */
    for( $i = 0; $i < $this->body->count(); $i++ ){
      /* Check if current position matches Value-Compare-Entity pattern */
      $hasFieldValue = $this->fieldIsValue( $items[ $i ]);
      $hasFieldCompare = $this->fieldIsCompare( $items[ $i + 1 ] ?? null);
      $hasFieldEntity = $this->fieldIsEntity( $items[ $i + 2 ] ?? null); 

      /* Swap positions and invert operator when pattern is found */
      if( $hasFieldValue && $hasFieldCompare && $hasFieldEntity ){
        [ $items[ $i ], $items[ $i + 1 ], $items[ $i + 2 ]] = [
          $items[$i + 2], $this->fieldCompareInvert( $items[$i + 1] ), $items[$i]
        ];
      }
    }
    
    /* Rebuild body collection with normalized items */
    $this->body = new Collection($items);
  }

  /**
   * Groups comparisons of the same field together for optimization
   * Moves duplicate field comparisons to be adjacent, facilitating pattern detection like BETWEEN
   */
  public function defineFieldComparesGroup(
  ): void {
    /* Get all items from body collection */
    $items = $this->body->all();
    
    /* Iterate through items to find duplicate field comparisons */
    for($i = 0; $i < count($items); $i++){
      /* Skip non-entity tokens */
      if(!$this->fieldIsEntity($items[$i])) continue;

      /* Store current field name for comparison */
      $currentField = $items[ $i ]->value;
      
      /* Search for duplicate field comparisons ahead */
      for($j = $i + 3; $j < Util::sizeArray( $items ); $j++){
        /* Check if found token is same field entity */
        if($this->fieldIsEntity( $items[ $j ]) && $items[$j]->value === $currentField ){
          /* Check if there's a logical operator before the comparison */
          $logicalPos = $j - 1;
          $hasLogical = isset( $items[ $logicalPos ]) && $items[ $logicalPos ]->taken === Token::Logical;
          
          /* Determine extraction position and length based on logical operator presence */
          $startPos = $hasLogical ? $logicalPos : $j;
          $length = $hasLogical ? 4 : 3;
          
          /* Extract the comparison block (with or without logical operator) */
          $comparison = array_splice(
            $items,
            $startPos,
            $length
          );

          /* Insert comparison block right after the first occurrence */
          $insertPos = $i + 3;
          
          array_splice( 
            $items,
            $insertPos,
            0, 
            $comparison
          );

          break;
        }
      }
    }
    
    /* Rebuild body collection with grouped comparisons */
    $this->body = new Collection($items);
  }
}