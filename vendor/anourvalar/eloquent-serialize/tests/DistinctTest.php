<?php

namespace AnourValar\EloquentSerialize\Tests;

use AnourValar\EloquentSerialize\Tests\Models\User;

class DistinctTest extends AbstractTest
{
    /**
     * @return void
     */
    public function testSimple()
    {
        $this->compare(User::distinct('title'));
    }
}
