<?php

declare(strict_types=1);

namespace Marktic\CMP\Base\Models\Traits;

trait BaseRepositoryTrait
{
    use HasDatabaseConnectionTrait;

    protected function initRelations(): void
    {
        parent::initRelations();
        $this->initRelationsCmp();
    }

    protected function initRelationsCmp(): void
    {
    }

    protected function generateController(): string
    {
        if (defined('static::CONTROLLER')) {
            return static::CONTROLLER;
        }

        return $this->getTable();
    }
}
