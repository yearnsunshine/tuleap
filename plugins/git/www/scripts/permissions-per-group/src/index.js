/*
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

import Vue                  from 'vue';
import GitPermissions       from './GitPermissions.vue';
import { gettext_provider } from './gettext-provider.js';

document.addEventListener('DOMContentLoaded', () => {
    const vue_mount_points = document.getElementById('git-permission-per-group');
    if (vue_mount_points) {
        const rootComponent    = Vue.extend(GitPermissions);
        const locale           = vue_mount_points.dataset.locale;
        gettext_provider.setLocale(locale);
        new rootComponent({
            propsData: { ...vue_mount_points.dataset }
        }).$mount(vue_mount_points);

    }
});