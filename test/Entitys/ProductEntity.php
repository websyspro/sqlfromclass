<?php

namespace Websyspro\Test\Entitys;

use Websyspro\Entity\Decorations\Statistics\Index;
use Websyspro\Entity\Decorations\Columns\Decimal;
use Websyspro\Entity\Decorations\Columns\Number;
use Websyspro\Entity\Decorations\Columns\Text;
use Websyspro\Entity\Core\Bases\BaseEntity;
use Websyspro\Entity\Decorations\Constraints\ForeignKey;
use Websyspro\Entity\Decorations\Constraints\OneToOne;

class ProductEntity
extends BaseEntity
{
  #[Text(255)]
  #[Index()]
  public string $Name;

  #[Decimal(10,2)]
  public float $Value;

  #[Number()]
  #[ForeignKey(ProductGroupEntity::class)]
  public int $ProductGroupId;

  #[OneToOne(ProductGroupEntity::class)]
  public ProductGroupEntity $ProductGroup;

  #[Text(1)]
  public string $State;

  #[Decimal(10,2)]
  public float $Amount;

  #[Decimal(10,2)]
  public float $TotalStock;
}