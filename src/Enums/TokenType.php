<?php

namespace Websyspro\SqlFromClass\Enums;

enum TokenType
{
  case FieldEntity;
  case FieldStatic;
  case FieldValue;
  case EnumValue;
  case Compare;
  case Logical;
  case StartParent;
  case EndParent;
  case Empty;
  case FieldIgnore;
}