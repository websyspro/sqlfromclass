<?php

use Websyspro\Test\Entitys\DocumentEntity;
use Websyspro\SqlFromClass\ArrowFnToSql;
use Websyspro\Test\Entitys\BoxEntity;
use Websyspro\SqlFromClass\Shareds;
use Websyspro\Test\Entitys\DocumentItemEntity;
use Websyspro\Test\Entitys\OperatorEntity;
use Websyspro\Test\Enums\DocumentState;


function Repository(
  int $boxId
): ArrowFnToSql {
  return Shareds::createTokens(
    fn(
      BoxEntity $box,
      OperatorEntity $operator,
      DocumentEntity $document,
      DocumentItemEntity $documentItem,
    ) => (
      $box->Id === $boxId &&
      $box->Id === $document->BoxId &&
      $box->OperatorId === $operator->Id &&
      $document->Id === $documentItem->DocumentId &&
      $document->CreatedAt >= '02/04/2022' &&
      $document->CreatedAt <= '15/04/2022' &&
      $document->Observations === "Documento cancelado" &&
      $document->Actived === null &&
      $document->State === [ 
        DocumentState::Finalizado, 
        DocumentState::Cancelado
      ]
    )
  );
}



$start = microtime( true );
$where = Repository( 6 )
  ->getStructure();

print_r($where->tokens);

$end = microtime( true );
$duration = ( $end - $start ) * 1000;

//print_r( $where->tokens );
echo "\nTempo de execução: " . $duration . " segundos\n";

/*
Exemplo de Erro:
[Internal Server Error] Mapping Error: The entity 'Websyspro\Test\Entitys\OperatorEntity' was declared in the function signature, but no relationship (OneToOne/OneToMany) was found in 'BoxEntity' or related entities.
*/