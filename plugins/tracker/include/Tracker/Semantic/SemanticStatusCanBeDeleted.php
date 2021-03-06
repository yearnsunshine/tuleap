<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker\Semantic;

use Tracker;
use Tuleap\Event\Dispatchable;

class SemanticStatusCanBeDeleted implements Dispatchable
{
    const NAME = 'semanticStatusCanBeDeleted';

    /**
     * @var Tracker
     */
    private $tracker;

    /**
     * @var bool
     */
    private $semantic_can_be_deleted = false;

    public function __construct(Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    /**
     * @return Tracker
     */
    public function getTracker()
    {
        return $this->tracker;
    }

    /**
     * @return bool
     */
    public function semanticCanBeDeleted()
    {
        return $this->semantic_can_be_deleted;
    }

    public function semanticIsDeletable()
    {
        $this->semantic_can_be_deleted = true;
    }
}
