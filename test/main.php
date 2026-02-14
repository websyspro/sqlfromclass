<?php

use Websyspro\SqlFromClass\FnBodyToWhere;
use Websyspro\SqlFromClass\Shareds;
use Websyspro\Test\Entitys\BoxEntity;
use Websyspro\Test\Entitys\DocumentEntity;
use Websyspro\Test\Enums\BoxState;

function where(
  int $boxId
): FnBodyToWhere {
  return Shareds::arrowFnToTokens(
    fn(
      BoxEntity $box,
      DocumentEntity $document
    ) => (
      $box->Id === $boxId && 
      $box->CreatedAt >= '02/02/2026' &&
      $box->State === BoxState::Close->value &&
      '14/02/2026' >= $box->CreatedAt &&
      $document->BoxId === $box->Id 
    )
  );
}

$where = where( 6 );
// print_r( $where );
