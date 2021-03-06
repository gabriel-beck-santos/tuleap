<?php
/**
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
namespace Tuleap\AgileDashboard\Widget;

class WidgetKanbanDeletor
{
    /**
     * @var WidgetKanbanDao
     */
    private $widget_kanban_dao;

    /**
     * @var WidgetKanbanConfigDAO
     */
    private $widget_config_dao;

    public function __construct(WidgetKanbanDao $widget_kanban_dao, WidgetKanbanConfigDAO $widget_config_dao)
    {
        $this->widget_kanban_dao = $widget_kanban_dao;
        $this->widget_config_dao = $widget_config_dao;
    }

    public function delete($id, $owner_id, $owner_type)
    {
        $this->widget_kanban_dao->delete($id, $owner_id, $owner_type);

        if ($this->isWidgetFiltered($id)) {
            $this->widget_config_dao->deleteConfigForWidgetId($id);
        }
    }

    private function isWidgetFiltered($id)
    {
        return $this->widget_config_dao->searchKanbanTrackerReportId($id) !== null;
    }
}
