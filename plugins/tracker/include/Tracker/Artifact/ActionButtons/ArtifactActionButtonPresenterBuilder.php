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

namespace Tuleap\Tracker\Artifact\ActionButtons;

use PFUser;
use Tracker_Artifact;

class ArtifactActionButtonPresenterBuilder
{
    /**
     * @var ArtifactNotificationActionButtonPresenterBuilder
     */
    private $notification_button_builder;
    /**
     * @var ArtifactIncomingEmailButtonPresenterBuilder
     */
    private $mail_button_builder;
    /**
     * @var ArtifactCopyButtonPresenterBuilder
     */
    private $artifact_copy_button_builder;

    /**
     * @var ArtifactMoveButtonPresenterBuilder
     */
    private $move_button_builder;

    public function __construct(
        ArtifactNotificationActionButtonPresenterBuilder $notification_button_builder,
        ArtifactIncomingEmailButtonPresenterBuilder $mail_button_builder,
        ArtifactCopyButtonPresenterBuilder $artifact_copy_button_builder,
        ArtifactMoveButtonPresenterBuilder $move_button_builder
    ) {
        $this->notification_button_builder  = $notification_button_builder;
        $this->mail_button_builder          = $mail_button_builder;
        $this->artifact_copy_button_builder = $artifact_copy_button_builder;
        $this->move_button_builder          = $move_button_builder;
    }

    public function build(PFUser $user, Tracker_Artifact $artifact)
    {
        $action_buttons = [];

        $original_email = $this->mail_button_builder->getIncomingEmailButton($user, $artifact);
        $copy_artifact  = $this->artifact_copy_button_builder->getCopyArtifactButton($user, $artifact);
        $notification   = $this->notification_button_builder->getNotificationButton($user, $artifact);

        if (\ForgeConfig::get('tracker_move_artifact_ui')) {
            $move_artifact  = $this->move_button_builder->getMoveArtifactButton($user, $artifact);
        }

        if ($original_email) {
            $action_buttons[]['section'] = $original_email;
        }
        if ($copy_artifact) {
            $action_buttons[]['section'] = $copy_artifact;
        }
        if (\ForgeConfig::get('tracker_move_artifact_ui') && $move_artifact) {
            $action_buttons[]['section'] = $move_artifact;
        }

        if (($original_email || $copy_artifact || $move_artifact) && $notification) {
            $action_buttons[]['divider'] = true;
        }

        if ($notification) {
            $action_buttons[]['section'] = $notification;
        }

        return new GlobalButtonsActionPresenter($action_buttons);
    }
}
