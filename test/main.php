<?php

use Dom\DocumentType;
use Websyspro\SqlFromClass\FnBodyToWhere;
use Websyspro\SqlFromClass\Shareds;
use Websyspro\Test\Enums\Role;
use Websyspro\Test\Access;
use Websyspro\Test\Entitys\BoxEntity;
use Websyspro\Test\Entitys\DocumentEntity;
use Websyspro\Test\User;

function where(
  int $boxId
): FnBodyToWhere {
  return Shareds::arrowFnToTokens(
    fn(
      BoxEntity $box,
      DocumentEntity $document
    ) => (
      $box->Id === $boxId
    )
  );
}

$where = where( 6 );
// print_r( $where );
