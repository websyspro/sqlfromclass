<?php

use Websyspro\SqlFromClass\FnBodyToWhere;
use Websyspro\SqlFromClass\Shareds;
use Websyspro\Test\Enums\Role;
use Websyspro\Test\Access;
use Websyspro\Test\User;

function where(
  string $email
): FnBodyToWhere {
  return Shareds::arrowFnToTokens(
    fn(
      User $user,
      Access $access
    ) => (
      $user->email === "Meu: $email" &&
        $user->ID === $access->userID && (
          $user->password === "qazwsx" Or (
            $user->name !== [ 'emerson-sousa', "thiago" ] &&
            $user->role !== Role::Admin &&
            $user->actived === !true &&
            $access->createdAt >= "01/10/2015"
          )
      ) &&  "25/10/2015" >= $access->createdAt
    )
  );
}

$where = where( "cpd.emersontsa.123@gmail.com" );
// print_r( $where );
