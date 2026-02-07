<?php

use Websyspro\SqlFromClass\FnBodyToWhere;
use Websyspro\SqlFromClass\Shareds;
use Websyspro\Test\Enums\Role;
use Websyspro\Test\Access;
use Websyspro\Test\User;

function where(
  string $email
): FnBodyToWhere {
  $arrowFnToTokens = Shareds::arrowFnToTokens(fn(
    User $user,
    Access $access
  ) => (
      $user->email === "Meu: $email" &&
        $user->ID === $access->userID && (
          $user->password === "qazwsx" || (
            $user->name === [ 'emerson-sousa', "thiago" ] &&
            $user->role !== Role::Admin &&
            $user->actived === !true
          )
      )
    )
  );

  return $arrowFnToTokens;
}

print_r(where( "cpd.emersontsa@gmail.com sdfasdfd" ));

