<?php

namespace Websyspro\Test\Entitys;

use Websyspro\Commons\DataList;
use Websyspro\Entity\Decorations\Statistics\Index;
use Websyspro\Entity\Decorations\Columns\Decimal;
use Websyspro\Entity\Decorations\Columns\Number;
use Websyspro\Entity\Decorations\Columns\Text;
use Websyspro\Entity\Core\Bases\BaseEntity;
use Websyspro\Entity\Decorations\Constraints\ForeignKey;
use Websyspro\Entity\Decorations\Constraints\OneToMany;
use Websyspro\Entity\Decorations\Constraints\OneToOne;

class DocumentEntity
extends BaseEntity
{
  #[Text(1)]
  #[Index(1)]
  public string $Type;

  #[Text(1)]
  public string $State;

  #[Number()]
  #[Index(2)]
  #[ForeignKey(BoxEntity::class)]
  public int $BoxId;

  #[OneToOne(BoxEntity::class)]
  public BoxEntity $Box;

  #[Number()]
  #[Index(2)]
  #[ForeignKey(OperatorEntity::class)]
  public int $OperatorId;

  #[OneToOne(OperatorEntity::class)]
  public OperatorEntity $Operator;

  #[Number()]
  #[ForeignKey(CustomerEntity::class)]
  public int $CustomerId;

  #[OneToOne(CustomerEntity::class)]
  public CustomerEntity $Customer;

  #[Decimal(10,2)]
  public float $Value;

  #[Decimal(10,2)]
  public float $ValueInPix;

  #[Decimal(10,2)]
  public float $ValueInDebitCard;

  #[Decimal(10,2)]
  public float $ValueInCreditCard;

  #[Decimal(10,2)]
  public float $InstallmentsFromCreditCard;

  #[Decimal(10,2)]
  public float $ValueInCash;

  #[Decimal(10,2)]
  public float $AmountReceived;

  #[Decimal(10,2)]
  public float $ValueChange;

  #[Text(255)]
  public string $Observations;

  #[OneToMany(DocumentItemEntity::class)]
  public DataList $DocumentItems;
}