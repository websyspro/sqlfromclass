<?php

namespace Websyspro\SqlFromClass\Enums;

enum TokenType
{
  case FieldEntity;
  case FieldStatic;
  case FieldValue;
  case FieldEnum;
  case Compare;
  case Logical;
  case StartParent;
  case EndParent;
  case Empty;
  case FieldIgnore;
}