<?php 

namespace Websyspro\Test\Entitys;

use Websyspro\Entity\Core\Bases\BaseEntity;
use Websyspro\Entity\Decorations\Constraints\Unique;
use Websyspro\Entity\Decorations\Statistics\Index;
use Websyspro\Entity\Decorations\Columns\Datetime;
use Websyspro\Entity\Decorations\Columns\Decimal;
use Websyspro\Entity\Decorations\Columns\Number;
use Websyspro\Entity\Decorations\Columns\Text;
use Websyspro\Entity\Decorations\Constraints\ForeignKey;

class BoxEntity 
extends BaseEntity
{
  #[Text(32)]
  #[Index()]
  #[Unique()]
  public string $Name;

  #[Text(1)]
  #[Unique()]
  public string $State;

  #[Number()]
  #[ForeignKey(OperatorEntity::class)]
  #[Unique(2)]
  public ?string $OperatorId;

  #[Text(255)]
  public string $Printer;

  #[Datetime()]
  public string $OpeningAt;

  #[Decimal(10,2)]
  public string $OpeningBalance;
}