<?php

use Websyspro\Test\Entitys\DocumentEntity;
use Websyspro\SqlFromClass\ArrowFnToSql;
use Websyspro\Test\Entitys\BoxEntity;
use Websyspro\SqlFromClass\Shareds;
use Websyspro\Test\Enums\BoxState;

function Repository(
  int $boxId
): ArrowFnToSql {
  return Shareds::createTokens(
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

$where = Repository( 6 )
  ->getSql();

print_r( $where );
