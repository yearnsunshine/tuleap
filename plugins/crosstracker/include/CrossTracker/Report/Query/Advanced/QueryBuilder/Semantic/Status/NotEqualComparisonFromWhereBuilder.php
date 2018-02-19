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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Semantic\Status;

use ParagonIE\EasyDB\EasyStatement;
use Tracker;
use Tuleap\CrossTracker\Report\Query\ParametrizedFromWhere;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

class NotEqualComparisonFromWhereBuilder implements FromWhereBuilder
{
    public function getFromWhere(Metadata $metadata, Comparison $comparison, array $trackers)
    {
        $tracker_ids = array_map(
            function (Tracker $tracker) {
                return $tracker->getId();
            },
            $trackers
        );
        $tracker_ids_statement = EasyStatement::open()->in('field.tracker_id IN (?*)', $tracker_ids);

        $from = "LEFT JOIN (
                    tracker_changeset_value AS changeset_value_status
                    INNER JOIN tracker_changeset_value_list AS tracker_changeset_value_status
                        ON (
                            tracker_changeset_value_status.changeset_value_id = changeset_value_status.id
                        )
                    INNER JOIN (
                        SELECT DISTINCT static.field_id, static.id
                        FROM tracker_field_list_bind_static_value AS static
                            INNER JOIN tracker_field AS field
                                ON field.id = static.field_id AND $tracker_ids_statement
                            INNER JOIN tracker_semantic_status AS SS1
                                ON field.id = SS1.field_id
                            LEFT JOIN tracker_semantic_status AS SS2
                                ON static.field_id = SS2.field_id AND static.id = SS2.open_value_id
                        WHERE SS2.open_value_id IS NULL
                    ) AS closed_values
                        ON (
                            changeset_value_status.field_id = closed_values.field_id
                        )
                ) ON (
                    changeset_value_status.changeset_id = tracker_artifact.last_changeset_id
                )";

        $where = "changeset_value_status.changeset_id IS NOT NULL
            AND tracker_changeset_value_status.bindvalue_id = closed_values.id";

        return new ParametrizedFromWhere($from, $where, $tracker_ids);
    }
}
