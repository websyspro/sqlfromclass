<?php

use Websyspro\Test\Entitys\DocumentEntity;
use Websyspro\SqlFromClass\ArrowFnToSql;
use Websyspro\Test\Entitys\BoxEntity;
use Websyspro\SqlFromClass\Shareds;
use Websyspro\SqlFromClass\Token;
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



$start = microtime(true);

$where = Repository( 6 )
  ->getSql();

$executionTime = (microtime(true) - $start) * 1000;

$sqlList = $where->tokens->mapper(fn( Token $t ) => $t->value );  
print_r( $sqlList->joinWithSpace());
echo "\nTempo de execução: " . number_format($executionTime, 4) . " ms\n";

//print_r( $where->tokens );


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