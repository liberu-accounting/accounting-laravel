<?php

namespace AnourValar\EloquentSerialize\Tests;

use AnourValar\EloquentSerialize\Tests\Models\User;

class GroupByTest extends AbstractTest
{
    /**
     * @return void
     */
    public function testSimple()
    {
        // One column
        $this->compare(User::groupBy('title'));
    }
}
