<?php

namespace Websyspro\Test\Entitys;

use Websyspro\Entity\Decorations\Constraints\Unique;
use Websyspro\Entity\Decorations\Columns\Text;
use Websyspro\Entity\Core\Bases\BaseEntity;

class OperatorEntity
extends BaseEntity
{
  #[Text(64)]
  #[Unique(1)]
  public string $Name;
}