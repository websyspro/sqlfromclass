<?php

use Websyspro\SqlFromClass\FnBody;
use Websyspro\SqlFromClass\Shareds;
use Websyspro\Test\Access;
use Websyspro\Test\User;

enum Role {
  case Admin;
  case User;
}

function where(
  string $email
): FnBody {
  return Shareds::arrowFnToString(
    fn(
    User $user,
    Access $access
  ) => (
      $user->email === $email &&
        $user->ID === $access->userID && (
          $user->password === "qazwsx" || (
            $user->name === [ 'emerson', "thiago" ] &&
            $user->role !== Role::Admin->name
          )
      )
    )
  );
}

print_r(
  where( "cpd.emersontsa@gmail.com" )
);