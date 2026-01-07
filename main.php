<?php

use Websyspro\SqlFromClass\FnBody;
use Websyspro\SqlFromClass\Shareds;
use Websyspro\Test\Access;
use Websyspro\Test\User;

function where(
  callable $fn
): FnBody {
  return Shareds::arrowFnToString(
    $fn
  );
}

function ParseSQL(
): FnBody {
  $where = where( fn(
    User $user,
    Access $access
  ) => 
    $user->email === "cpd.emersontsa@gmail.com" &&
    $user->ID === $access->userID
  );

  return $where;
}

print_r(ParseSQL());