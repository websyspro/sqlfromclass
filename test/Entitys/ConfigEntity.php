<?php

namespace Websyspro\Test\Entitys;

use Websyspro\Entity\Decorations\Columns\Decimal;
use Websyspro\Entity\Decorations\Columns\Number;
use Websyspro\Entity\Decorations\Columns\Text;
use Websyspro\Entity\Core\Bases\BaseEntity;
use Websyspro\Entity\Decorations\Constraints\ForeignKey;

class ConfigEntity
extends BaseEntity
{
  #[Text(32)]
  public string $PasswordReleaseDiscount;

  #[Decimal(10,2)]
  public string $PurchaseLimitPerCustomer;

  #[Number()]
  #[ForeignKey(BoxEntity::class)]
  public ?string $MainBox;
}