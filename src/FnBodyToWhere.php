<?php

namespace Websyspro\SqlFromClass;

use ReflectionFunction;
use Websyspro\Commons\Collection;
use Websyspro\Commons\Util;
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
    public Collection $static,
    public Collection $body
  ){
    /* Process entity definitions and priorities */
    $this->defineEntityAndPriority();
    print_r($this);
  }

  /**
   * Checks if an entity is primary by searching for it in the function parameters
   */
  private function rntityPrimary(
    string $entity
  ): bool {
    /* Search through parameters to find if entity exists as a parameter */
    return $this->paramters->find( fn( FnParameter $fnParameter ) => (
      $fnParameter->entity === $entity
    )) !== null;
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
          $tokenList->entityIsPrimary = $this->rntityPrimary( $tokenList->entity );
        }

        return $tokenList;
      }
    );
  }
}