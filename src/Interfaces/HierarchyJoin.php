<?php

namespace Websyspro\SqlFromClass\Interfaces;

use Websyspro\SqlFromClass\Enums\EntityRoot;

class HierarchyJoin {
  public function __construct(
    public string $entity,
    public array $entityHistory = [],
    public string|null $entityParent = null,
    public EntityRoot $entityRoot = EntityRoot::Yes,
    public EntityJoin|null $entityJoin = null
  ){}
}