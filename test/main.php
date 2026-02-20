<?php

use Websyspro\Test\Entitys\DocumentEntity;
use Websyspro\SqlFromClass\ArrowFnToSql;
use Websyspro\Test\Entitys\BoxEntity;
use Websyspro\SqlFromClass\Shareds;
use Websyspro\Test\Entitys\DocumentItemEntity;
use Websyspro\Test\Entitys\OperatorEntity;
use Websyspro\Test\Enums\BoxState;

function Repository(
  int $boxId
): ArrowFnToSql {
  return Shareds::createTokens(
    fn(
      BoxEntity $box,
      DocumentEntity $document,
      DocumentItemEntity $documentItem,
      OperatorEntity $operator
    ) => (
      $box->Id === $boxId && 
      $box->CreatedAt >= '02/02/2026' &&
      $box->State === BoxState::Close->value &&
      '14/02/2026' >= $box->CreatedAt &&
      $document->BoxId === $box->Id &&
      $operator->Id === $document->OperatorId &&
      $documentItem->DocumentId === $document->Id 
    )
  );
}

$where = Repository( 6 )
  ->getSql();

print_r( $where );


// Sintaxe PHP,Valor Detectado,Tradução SQL,Exemplo
// ===,String com %,LIKE,col LIKE '%Teste%'
// !==,String com %,NOT LIKE,col NOT LIKE '%Teste%'
// ===,"Array [1, 2, 3]",IN,"col IN (1, 2, 3)"
// !==,"Array [1, 2, 3]",NOT IN,"col NOT IN (1, 2, 3)"
// ===,null,IS NULL,col IS NULL
// !==,null,IS NOT NULL,col IS NOT NULL

/*
Exemplo de Erro:
[Internal Server Error] Mapping Error: The entity 'Websyspro\Test\Entitys\OperatorEntity' was declared in the function signature, but no relationship (OneToOne/OneToMany) was found in 'BoxEntity' or related entities.
*/