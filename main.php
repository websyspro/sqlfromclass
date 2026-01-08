<?php

use Websyspro\SqlFromClass\FnBodyToWhere;
use Websyspro\SqlFromClass\Shareds;
use Websyspro\Test\Access;
use Websyspro\Test\User;

enum Role {
  case Admin;
  case User;
}

function where(
  string $email
): FnBodyToWhere {
  $arrowFnToTokens = Shareds::arrowFnToTokens(fn(
    User $user,
    Access $access
  ) => (
      $user->email === $email &&
        $user->ID === $access->userID && (
          $user->password === "qazwsx" || (
            $user->name === [ 'emerson', "thiago" ] &&
            $user->role !== Role::Admin->name &&
            $user->actived === !true
          )
      )
    )
  );

  return $arrowFnToTokens;
}

where( "cpd.emersontsa@gmail.com" );