/*
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

const path               = require('path');
const webpack_config     = require('./webpack.config.js');
const karma_configurator = require('../../../../tools/utils/scripts/karma-configurator.js');

webpack_config.entry = null;

module.exports = function(config) {
    const coverage_dir = path.resolve(__dirname, './coverage');
    const base_config  = karma_configurator.setupBaseKarmaConfig(
        config,
        webpack_config,
        coverage_dir
    );

    Object.assign(base_config, {
        files  : [
            'tlp-mock.spec.js',
            'cross-tracker/src/app/app.spec.js'
        ],
        preprocessors: {
            'cross-tracker/src/app/app.spec.js': ['webpack']
        }
    });

    config.set(base_config);
};
