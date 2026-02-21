<?php

namespace Websyspro\SqlFromClass;

use Websyspro\SqlFromClass\Interfaces\HierarchyJoin;
use Websyspro\SqlFromClass\Interfaces\EntityJoin;
use Websyspro\SqlFromClass\Interfaces\ParamIndex;
use Websyspro\SqlFromClass\Enums\EntityPriority;
use Websyspro\SqlFromClass\Enums\TokenType;
use Websyspro\SqlFromClass\Enums\EntityRoot;
use Websyspro\Entity\Enums\AttributeType;
use Websyspro\Entity\Shareds\ForeignKey;
use Websyspro\Entity\Enums\ColumnType;
use Websyspro\Entity\Shareds\Entity;
use Websyspro\Commons\Collection;
use Websyspro\Commons\Util;
use ReflectionFunction;
use BackedEnum;
use UnitEnum;

/**
 * Converts function body tokens to WHERE clause conditions by analyzing
 * field entities and determining their relationships to function parameters
 */
class ArrowFnToSql
{
  public Collection|null $params = null;

  /**
   * Class constructor that initializes the function body to WHERE clause converter
   * @param ReflectionFunction $reflectionFunction The reflected function to analyze
   * @param Collection $paramters Collection of function parameters
   * @param Collection $static Collection of static values
   * @param Collection $joins Collection of joins values
   * @param Collection $tokens Collection of body tokens to process
   */
  public function __construct(
    public ReflectionFunction $reflectionFunction,
    public Collection $paramters,
    public Collection $statics,
    public Collection $joins,
    public Collection $uses,
    public Collection $tokens
  ){}

  public function getSql(
  ): ArrowFnToSql {
    $this->defineStructure();
    return $this;
  }

  private function defineStructure(
  ): void {
    $this->defineFieldPriority();
    $this->defineFieldEntity();
    $this->defineFieldEnums();
    $this->defineFieldStatics();
    $this->defineFieldSides();
    $this->defineFieldGroups();
    $this->defineFieldInJoins();
    $this->defineFieldHierarchy();
    $this->defineFieldValues();
    $this->defineFieldCompactar();
    $this->defineFieldCompared();
    $this->defineFieldParameters();
  }

  /**
   * Maps through the body tokens to identify and assign entity information
   * to field entity tokens, determining their corresponding entity name
   * and whether they represent primary entities based on function parameters
   */
  private function defineFieldPriority(
  ): void {
    /* Map through each token in the body collection */
    $this->tokens = $this->tokens->mapper(
      function( Token $token ) {
        /* Check if current token is a field entity type */
        if( $token->takenType === TokenType::FieldEntity ) {
          /* Extract parameter name by removing $ prefix and -> suffix, then find matching entity */
          $token->entity = $this->paramters->find( fn( Parameter $parameter ) => (
            $parameter->name === preg_replace( [ "#^\\$#", "#->.*$#" ], "", $token->value )
          ))->entity;

          /* Convert entity class to readable name format */
          $token->entityName = $this->entityName( $token->entity );
          /* Check if this entity is marked as primary */
          $token->entityPriority = $this->entityPrimary( $token->entity );
        }

        /* return tokenList */
        return $token;
      }
    );
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
    if($parameterFirst instanceof Parameter){
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
   * Transforms field entity tokens by replacing parameter references with entity names
   * Converts tokens like "$parameter->field" to "EntityName.field" format
   */
  private function defineFieldEntity(
  ): void {
    /* Map through body tokens to transform field entity references */
    $this->tokens = $this->tokens->mapper(
      function( Token $token ) {
        /* Process only field entity tokens */
        if( $token->takenType === TokenType::FieldEntity ){
          /* Replace parameter reference with entity name using regex pattern */
          $token->value = preg_replace(
            "#^\\$.*->#",
            "{$token->entityName}.", 
            $token->value
          );

          $newToken = new Collection(
            preg_split( 
              "#\.#", 
              $token->value,
              -1, 
              PREG_SPLIT_NO_EMPTY
            )
          );

          $token->entityField = $newToken->last();
        }

        /* Return the processed token */
        return $token;
      }
    );
  }

  /**
   * Checks if a token is a field entity type
   * @param Token|null $tokenList The token to check
   * @return bool Returns true if token is a FieldEntity, false otherwise
   */
  private function fieldIsEntity(
    Token|null $token
  ): bool {
    /* Check if token exists and is a FieldEntity type */
    return isset( $token ) && $token->takenType === TokenType::FieldEntity;
  }

  private function fieldIsEntityIsPriority(
    Token|null $token
  ): bool {
    /* Check if token exists and is a FieldEntity type */
    return isset( $token ) 
        && $token->takenType === TokenType::FieldEntity 
        && $token->entityPriority === EntityPriority::Primary;
  }  

  /**
   * Checks if a token is a comparison operator
   * @param Token|null $token The token to check
   * @return bool Returns true if token is a Compare operator, false otherwise
   */
  private function fieldIsCompare(
    Token|null $token
  ): bool {
    /* Check if token exists and is a Compare operator type */
    return isset( $token ) && $token->takenType === TokenType::Compare;
  }

  private function fieldIsLogical(
    Token|null $token
  ): bool {
    /* Check if token exists and is a Compare operator type */
    return isset( $token ) && $token->takenType === TokenType::Logical;
  }  

  /**
   * Inverts comparison operators for reversed field comparisons
   * @param Token $tokenList The token containing the comparison operator
   * @return Token Returns the token with inverted operator (>= becomes <=, > becomes <, etc)
   */
  private function fieldCompareInvert(
    Token $tokenList
  ): Token {
    /* Invert comparison operator using match expression */
    $tokenList->value = match( $tokenList->value ){
      ">=" => "<=", "<=" => ">=", ">" => "<", "<" => ">",
      default => $tokenList->value
    };

    return $tokenList;
  }

  /**
   * Checks if a token is a field value type
   * @param Token|null $tokenList The token to check
   * @return bool Returns true if token is a FieldValue, false otherwise
   */
  private function fieldIsValue(
    Token|null $token
  ): bool {
    /* Check if token exists and is a FieldValue type */
    return isset( $token ) && $token->takenType === TokenType::FieldValue;
  }  

  /**
   * Assigns entity metadata to field value tokens based on adjacent comparison tokens
   * Copies entity information from the related field entity to the value token
   */
  private function defineFieldHierarchy(
  ): void {
    /* Map through body tokens to assign entity metadata to field values */
    $this->tokens = $this->tokens->mapper(
      function( Token $token, int $i ) {
        /* Check if current token is a field value type */
        $hasFieldProps = $token->takenType === TokenType::FieldValue 
                      || $token->takenType === TokenType::EnumValue 
                      || $token->takenType === TokenType::FieldStatic;

        /* Process only field value tokens */
        if( $hasFieldProps ){
          /* Check if previous token exists (Value after Entity pattern) */
          if( $this->tokens->eq( $i - 1 )->exist() ){
            $tokenListPrev = $this->tokens->eq( $i - 1 )->first();
            /* Verify previous token is a comparison operator */
            if( $tokenListPrev->takenType === TokenType::Compare ){
              $tokenListComparePrev = $this->tokens->eq( $i - 2 )->first();

              /* Copy entity metadata from the field entity to this value */
              if( $tokenListComparePrev instanceof Token ){
                $token->entity = $tokenListComparePrev->entity;
                $token->entityName = $tokenListComparePrev->entityName;
                $token->entityField = $tokenListComparePrev->entityField; 
                $token->entityPriority = $tokenListComparePrev->entityPriority;
              }
            }
          } else
          /* Check if next token exists (Value before Entity pattern) */
          if( $this->tokens->eq( $i + 1 )->exist() ){
            $tokenListNext = $this->tokens->eq( $i + 1 )->first();
            /* Verify next token is a comparison operator */
            if( $tokenListNext->takenType === TokenType::Compare ){
              $tokenListCompareNext = $this->tokens->eq( $i + 2 )->first();

              /* Copy entity metadata from the field entity to this value */
              if( $tokenListCompareNext instanceof Token ){
                $token->entity = $tokenListCompareNext->entity;
                $token->entityName = $tokenListCompareNext->entityName;
                $token->entityField = $tokenListCompareNext->entityField;
                $token->entityPriority = $tokenListCompareNext->entityPriority;
              }
            }
          }
        }

        return $token;
      }
    );    
  }

  private function decodeEnum(
    string $enumValue
  ): string {
    $isNotEnum = !preg_match( 
      "#^([\w\\\]+)::(\w+)(?:->(\w+))?$#", 
      $enumValue, 
      $enumPaths 
    );

    if( $isNotEnum ){
      return $enumValue;
    }

    $className = $enumPaths[1];
    $caseName = $enumPaths[2];
    $property = $enumPaths[3] ?? null;

    if( str_starts_with( $className, "\\" ) === false ){
      $classNameFull = $this->uses->where(
        fn( UseClass $useClass ) => (
          $useClass->class === $className
        )
      );

      if( $classNameFull->exist() ){
        $className = Util::sprintFormat( 
          "\\%s\\%s", [
            $classNameFull->first()->path,
            $classNameFull->first()->class
          ]
        );
      }
    }

    if( enum_exists($className) === false && class_exists($className) === false) {
      throw new \Exception("Classe ou Enum {$className} não encontrada.");
    }          

    $enumCase = constant( 
      "{$className}::{$caseName}"
    );

    if( $enumCase instanceof UnitEnum ){
      if( $property === "name" ){
        return $enumCase->name;
      } else
      if( $property === "value" && $enumCase instanceof BackedEnum ){
        return $enumCase->value;
      } else
      if( $property === null ){
        return ( $enumCase instanceof BackedEnum ) 
          ? $enumCase->value 
          : $enumCase->name;
      }
    } else {
      return $enumCase;
    }
    
    return $enumValue;
  }

  /**
   * Processa tokens de enum values, convertendo referências de enum para seus valores
   * Transforma tokens para o nome do valor do enum
   */
  private function defineFieldEnums(
  ): void {
    $this->tokens = $this->tokens->mapper(
      function( Token $token ) {
        /* Processa apenas tokens de enum value */
        if( $token->takenType === TokenType::EnumValue ){
          $token->value = $this->decodeEnum(
            $token->value
          );
        }

        return $token;
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
    $this->tokens = $this->tokens->mapper(
      function( Token $token ) {
        /* Process only field static tokens */
        if( $token->takenType === TokenType::FieldStatic ){
          /* Parse and replace static variable references */
          $token->value = $this->parseFromStatics( 
            $token->value
          );
        }

        return $token;
      }
    );
  }

  /**
   * Normalizes comparison order by ensuring field entities are always on the left side
   * Transforms "Value Compare Entity" patterns to "Entity Compare Value" and inverts operators
   */
  public function defineFieldSides(
  ): void {
    $items = $this->tokens->all();
    $count = Util::sizeArray( $items);

    for( $i = 0; $i < $count - 2; $i++ ){
      $left   = $items[ $i ];
      $center = $items[ $i + 1 ];
      $right  = $items[ $i + 2 ];

      if (!$this->fieldIsCompare($center)) {
        continue;
      }

      $shouldSwap = false;

      // Regra 1: Valor vs Entidade (Sempre mover entidade para esquerda)
      if( $this->fieldIsValue( $left ) && $this->fieldIsEntity( $right )){
        $shouldSwap = true;
      } 
      // Regra 2: Entidade vs Entidade (Mover Primary para esquerda se o outro for Secundary)
      elseif ($this->fieldIsEntity( $left ) && $this->fieldIsEntity( $right )) {
        if( $this->fieldIsEntityIsPriority( $right ) && $this->fieldIsEntityIsPriority( $left ) === false){
          $shouldSwap = true;
        }
      }

      if ($shouldSwap) {
        $items[$i] = $right;
        $items[$i + 1] = $this->fieldCompareInvert($center);
        $items[$i + 2] = $left;
        
        // Salta o operador e o operando já ajustados para evitar re-análise
        $i += 2; 
      }
    }

    $this->tokens = new Collection($items);
  }

  /**
   * Groups comparisons of the same field together for optimization
   * Moves duplicate field comparisons to be adjacent, facilitating pattern detection like BETWEEN
   */
  public function defineFieldGroups(  
  ): void {
    $items = $this->tokens->all();
    $count = $this->tokens->count();

    for( $i = 0; $i < $count; $i++ ){
      $fieldEntity = $this->fieldIsEntity( 
        $items[ $i ] ?? null
      );

      if( $fieldEntity === false ){
        continue;
      };

      for( $j = $i + 3; $j < $count; $j++ ){
        $compareFieldEntity = $this->fieldIsEntity( 
          $items[ $j ] ?? null
        );

        $hasFieldsEquals = $compareFieldEntity && (
          $items[ $i ]->value === $items[ $j ]->value
        );

        if( $hasFieldsEquals === true ){
          $hasPrevIsLogical = $this->fieldIsLogical( 
            $items[ $j - 1 ] ?? null
          );

          $groupMoved = array_splice(
            $items, 
            $hasPrevIsLogical ? $j - 1 : $j, 
            $hasPrevIsLogical ? 4 : 3
          );

          array_splice(
            $items, 
            $i + 3, 0, 
            $groupMoved
          );

          $i = $i + 2;
        }
      }
    }

    $this->tokens = new Collection(
      $items
    );
  }

  private function defineFieldInJoins(
    string|null $entity = null,
    string|null $entityParent = null,
    array $entityHistory = [],
    AttributeType $attributeType = AttributeType::oneToOne,
  ): void {
    $entityAlreadyAdded = $this->joins->where(
      fn( HierarchyJoin $hierarchyJoin ) => (
        $hierarchyJoin->entity === $entity
      )
    );

    if( $entityAlreadyAdded->exist() === false ){
      $parameter = $entity !== null
        ? $this->paramters->where( 
            fn( Parameter $parameter ) => (
              $parameter->entity === $entity
            ) 
          )
        : $this->paramters;

      if( $parameter->exist() === true ){
        [ $parameter ] = $parameter->all();

        $entityHistory = array_merge( 
          $entityHistory, [ 
            $attributeType 
          ]
        );

        $entityRoot = Util::sizeArray(
          Util::where( $entityHistory, 
          fn( AttributeType $attributeType ) => (
              $attributeType === AttributeType::oneToMany
            )
          )
        ) === 0 ? EntityRoot::Yes : EntityRoot::No;

        if( $entity !== null ){
          $entityFromParameter = $this->getEntityFromParam( $parameter->entity );
          $entityParentFromParameter = $this->getEntityFromParam( $entityParent );

          if( $attributeType === AttributeType::oneToOne ){
            $foreigns = $entityParentFromParameter->entityStructure->foreigns->where(
              fn( ForeignKey $foreignKey ) => (
                $foreignKey->reference->entity->class === $parameter->entity &&
                $foreignKey->entity->class === $entityParent
              )
            );
          } else if( $attributeType === AttributeType::oneToMany ) {
            $foreigns = $entityFromParameter->entityStructure->foreigns->where(
              fn( ForeignKey $foreignKey ) => (
                $foreignKey->reference->entity->class === $entityParent &&
                $foreignKey->entity->class === $parameter->entity
              )
            );            
          }
        }
        
        $this->joins->add(
          new HierarchyJoin(
            $parameter->entity,
            $entityHistory,
            $entityParent,
            $entityRoot,
            isset( $foreigns ) && $foreigns->exist() 
              ? new EntityJoin( $foreigns->first() ) 
              : null
          )
        );

        $parameter->entityStructure->oneToOne->mapper(
          fn(Entity $entity) => $this->defineFieldInJoins( 
            $entity->class, 
            $parameter->entity, 
            $entityHistory, 
            AttributeType::oneToOne
          )
        );    

        $parameter->entityStructure->oneToMany->mapper(
          fn(Entity $entity) => $this->defineFieldInJoins( 
            $entity->class, 
            $parameter->entity, 
            $entityHistory,
            AttributeType::oneToMany
          )
        );
      }  
    }
  }  

  private function getEntityFromParam(
    string $entity
  ): object|null {
    $columns = $this->paramters->where( 
      fn( Parameter $parameter ) => (
        $parameter->entity === $entity
      )
    );

    return $columns->exist() ? $columns->first() : null;
  }

  private function defineFieldValues(
  ): void {
    $this->tokens->mapper(
      function( Token $token ) {
        if( $token->takenType === TokenType::FieldValue ){
          $parameter = $this->getEntityFromParam( $token->entity );

          $hasFieldExist = $parameter->entityStructure->types->get( $token->entityField ) !== null;
          $hasFieldColumnExists = isset( $parameter->entityStructure->types->get( $token->entityField )->instance );

          if( $hasFieldExist && $hasFieldColumnExists && $parameter !== null){
            $columnType = $parameter->entityStructure->types->get( $token->entityField )->instance->columnType;
            $token->value = $columnType->Encode($token->value);
          }
        }

        return $token;
      }
    );
  }

  private function hasBetween(
    int $index
  ): bool {
    $tokens = $this->tokens->slice(
      $index, 7
    );

    if( $tokens->exist() && $tokens->count() === 7 ){
      [ $fieldLeft1, $compare1, $_, $logical, 
        $fieldLeft2, $compare2 
      ] = $tokens->all();

      $hasFieldsEntitys = $fieldLeft1->takenType === TokenType::FieldEntity 
                       && $fieldLeft2->takenType === TokenType::FieldEntity;

      if( $hasFieldsEntitys === true ){
        $field1Columns = $this->getEntityFromParam( $fieldLeft1->entity );
        $field2Columns = $this->getEntityFromParam( $fieldLeft2->entity );

        $hasfield1ColumnType = in_array(
          $field1Columns->entityStructure->types->get( $fieldLeft1->entityField )->instance->columnType, [
            ColumnType::date, ColumnType::datetime, ColumnType::number
          ]
        );

        $hasfield2ColumnType = in_array(
          $field2Columns->entityStructure->types->get( $fieldLeft2->entityField )->instance->columnType, [
            ColumnType::date, ColumnType::datetime, ColumnType::number
          ]
        );

        $hasComparesInverted = $compare1->value === ">=" 
                            && $compare2->value === "<=";
        
        $hasLogicalAnd = strtolower( 
          $logical->value 
        ) === "and";

        return $fieldLeft1->entityName === $fieldLeft2->entityName 
            && $fieldLeft1->entityField === $fieldLeft2->entityField
            && $hasComparesInverted
            && $hasLogicalAnd
            && $hasfield1ColumnType 
            && $hasfield2ColumnType;
      }
    }

    return false;
  }

  private function defineFieldCompactar(
  ): void {
    $items = $this->tokens->all();
    $count = $this->tokens->count();
    $body  = new Collection();
    
    for( $i = 0; $i < $count; $i++ ){
      if( $this->hasBetween( $i ) === true ){
        $body->add( $items[ $i ]);
        $body->add( new Token(
          TokenType::Logical,
          "Between"
        ));
        $body->add( $items[ $i + 2 ]);
        $body->add( $items[ $i + 3 ]);
        $body->add( $items[ $i + 6 ]); 

        $i += 6;
      } else {
        $body->add( $items[ $i ] );
      }
    }

    $this->tokens = $body;
  }

  private function defineFieldCompared(
  ): void {
    $this->tokens = $this->tokens->mapper(
      function( Token $token, int $index ) {
        if( $token->takenType === TokenType::Compare ){
          $tokenNext = $this->tokens->get( $index + 1);
          if( $tokenNext !== null && $tokenNext instanceof Token ){
            $hasLike = strpos( 
              $tokenNext->value, 
              "%"
            ) === true;

            $hasList = Util::match(
              "#^\($#", 
              $tokenNext->value
            );
            
            $hasNull = strtoupper(
              $tokenNext->value
            ) === "NULL";

            if( $token->value === "=" && $hasLike ){
              $token->value = "Like";
            } else
            if( $token->value === "=" && $hasLike ){
              $token->value = "Not Like";
            } else
            if( $token->value === "=" && $hasList ){
              $token->value = "In";
            } else
            if( $token->value === "<>" && $hasList ){
              $token->value = "Not In";
            } else
            if( $token->value === "=" && $hasNull ){
              $token->value = "Is";
            } else
            if( $token->value === "<>" && $hasNull ){
              $token->value = "Not";
            }
          }
        }

        return $token;
      }
    );  
  }
  
  private function defineFieldParameters(): void {
    $this->tokens = $this->tokens->mapper(
      function( Token $token, int $index ) {
        $hasParameters = Util::inArray( 
          $token->takenType, [ 
            TokenType::FieldStatic,
            TokenType::FieldValue,
            TokenType::EnumValue
          ]
        );

        if( $hasParameters === true ){
          if( $this->params === null ){
            $this->params = new Collection();
          }
          
          $this->params->add( 
            new ParamIndex( 
              $index, 
              $token->value
            )
          );

          $token->value = $this->params->last()->getAlias();
        }

        return $token;
      }
    );
  }
}