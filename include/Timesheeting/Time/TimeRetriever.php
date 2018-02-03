<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Timesheeting\Time;

use PFUser;
use Tracker_Artifact;
use Tuleap\Timesheeting\Permissions\PermissionsRetriever;

class TimeRetriever
{
    /**
     * @var TimeDao
     */
    private $dao;
    /**
     * @var PermissionsRetriever
     */
    private $permissions_retriever;

    public function __construct(TimeDao $dao, PermissionsRetriever $permissions_retriever)
    {
        $this->dao                   = $dao;
        $this->permissions_retriever = $permissions_retriever;
    }

    /**
     * @return Time[]
     */
    public function getTimesForUser(PFUser $user, Tracker_Artifact $artifact)
    {
        $times = array();

        if ($this->permissions_retriever->userCanSeeAggregatedTimesInTracker($user, $artifact->getTracker())) {
            foreach ($this->dao->getAllTimesAddedInArtifact($artifact->getId()) as $row_time) {
                $times[] = $this->buildTimeFromRow($row_time);
            }
        } else if ($this->permissions_retriever->userCanAddTimeInTracker($user, $artifact->getTracker())) {
            foreach ($this->dao->getTimesAddedInArtifactByUser($user->getId(), $artifact->getId()) as $row_time) {
                $times[] = $this->buildTimeFromRow($row_time);
            }
        }

        return $times;
    }

    /**
     * @return Time
     */
    private function buildTimeFromRow($row_time)
    {
        return new Time(
            $row_time['user_id'],
            $row_time['artifact_id'],
            $row_time['day'],
            $row_time['minutes'],
            $row_time['step']
        );
    }
}