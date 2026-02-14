<?php

namespace Websyspro\Test\Entitys;

use Websyspro\Entity\Core\Bases\BaseEntity;
use Websyspro\Entity\Decorations\Columns\Datetime;
use Websyspro\Entity\Decorations\Columns\Text;
use Websyspro\Entity\Decorations\Constraints\Unique;

class CustomerEntity
extends BaseEntity
{
  #[Text(255)]
  public string $Name;

  #[Text(14)]
  #[Unique()]
  public string $Cpf;

  #[Datetime()]
  public string $LastPurchaseAt;
}