<?php

namespace App\Models\Traits;

use App\Models\Model;
use Exception;

/**
 * @mixin  Model
 */
trait AsView
{
    /**
     * @throws Exception
     */
    public function save(array $options = []): never
    {
        throw new Exception('This model is read-only.');
    }

    /**
     * @throws Exception
     */
    public function delete(array $options = []): never
    {
        throw new Exception('This model is read-only.');
    }

    /**
     * @throws Exception
     */
    public function update(array $options = [], array $attributes = []): never
    {
        throw new Exception('This model is read-only.');
    }
}
