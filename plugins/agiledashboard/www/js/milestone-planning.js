/* 
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
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
var tuleap = tuleap || { };
tuleap.agiledashboard = tuleap.agiledashboard || { };
   
(function ($) {
    tuleap.agiledashboard.NewPlanning = Class.create({
        initialize: function () {
            var self = this;
            
            $('.agiledashboard-planning-submilestone-header').click(function (event) {
                var $submilestone_content_row = $(this).next();

                if ($submilestone_content_row.is(':visible')) {
                    $submilestone_content_row.hide();
                } else {
                    var data_container = $submilestone_content_row.find(".submilestone-data");

                    if (! isSubmilestoneDataLoaded(data_container)) {
                        self.fetchSubmilestoneData(data_container);
                    }
                    $submilestone_content_row.show();
                }
                $(this).find('i').toggleClass('icon-chevron-down').toggleClass('icon-chevron-right');
                event.preventDefault();
            });
            $('.agiledashboard-planning-submilestone-header').next().hide();

            function isSubmilestoneDataLoaded(data_container) {
                return data_container.attr("data-loaded") === "true";
            }

            this.makeSubmilestonesSortable();
        },

        fetchSubmilestoneData : function(data_container) {
            var self = this;

            $.ajax({
                url : "/plugins/agiledashboard/?action=submilestonedata",
                dataType : "html",
                data : {
                    planning_id: data_container.attr('data-planning-id'),
                    aid : data_container.attr('data-submilestone-id')
                },
                method : "get",
                success : function(data) {
                    self.setSubmilestoneDataLoaded(data_container);
                    data_container.find('tbody').append(data);
                    self.setSubmilestonesEditLinks(data_container);
                    self.updateSubmilestoneCapacities();
                    self.makeSubmilestonesSortable();
                }
            });
         },

        setSubmilestoneDataLoaded : function(data_container) {
            data_container.attr("data-loaded", "true");
        },

        setSubmilestonesEditLinks : function(data_container) {
            var urls                  = $('tr.submilestone-element td > a', data_container);
            var milestone_id          = this.getMilestoneId();
            var milestone_planning_id = this.getMilestonePlanningId();

            urls.each( function(index, url) {
                var new_url = $(url).attr('href') + '&' + 'planning[planning][' + milestone_planning_id + ']=' + milestone_id;

                $(url).attr('href', new_url);
            });

        },

        getMilestoneId : function() {
            return $('div.agiledashboard-planning-backlog').attr('data-milestone-id');
        },

        getMilestonePlanningId : function() {
            return $('div.agiledashboard-planning-backlog').attr('data-milestone-planning-id');
        },

        updateSubmilestoneCapacities : function() {
            var submilestones = $(".submilestone-data");

            submilestones.each(function(index, submilestone) {
                var capacity = 0,
                    capacities = $(submilestone).find(".submilestone-element-capacity");

                capacities.each(function(){
                    var element_capacity = parseFloat($(this).html());
                    
                    if (! isNaN(element_capacity)) {
                        capacity += parseFloat(element_capacity);
                    }
                });

                $(submilestone).find(".submilestone-capacity").html(capacity);
            });
        },

        establishWidthOfCellsToBeConstitentWhileDragging : function($milestone_content_rows) {
            console.log('tata');
            $milestone_content_rows.children().each(function() {
                $(this).children().each(function() {
                    $(this).width($(this).width());
                });
            });
        },

        makeSubmilestonesSortable : function() {
            var self = this,
                from_submilestone_id,
                user_can_plan = $("[data-can-plan]").attr("data-can-plan");

            if (user_can_plan !== "true") {
                return;
            }

            var $submilestone_element_rows = $(".submilestone-element-rows");

            $submilestone_element_rows.sortable({
                connectWith: ".submilestone-element-rows",
                dropOnEmpty: true,
                scroll: true,
                tolerance : "pointer",
                scrollSensitivity: 50,
                items : ".submilestone-element",
                helper: function (e, tr) {
                    var $originals = tr.children();
                    var $helper = tr.clone();
                    $helper.children().each(function (index) {
                        // Set helper cell sizes to match the original size
                        $(this).width($originals.eq(index).width());
                    });

                    return $helper;
                },
                start : function (event, ui) {
                    from_submilestone_id = $(event.target).parents(".submilestone-data").first().attr('data-submilestone-id');
                },
                stop: function (event, ui) {
                    var item               = ui.item,
                        rowIdentifier      = "data-artifact-id",
                        item_id            = $(item).attr(rowIdentifier),
                        next_id            = $(item).next(".submilestone-element").attr(rowIdentifier),
                        prev_id            = $(item).prev(".submilestone-element").attr(rowIdentifier),
                        to_submilestone_id = getToSubmilestoneId();

                        updateElement();
                        self.updateSubmilestoneCapacities();

                        function getToSubmilestoneId() {
                            return $(ui.item).parents(".submilestone-data").first().attr('data-submilestone-id');
                        }

                        function updateElement() {
                            if(to_submilestone_id == from_submilestone_id) {
                                 sort();
                            } else if (typeof(to_submilestone_id) === "undefined" && typeof(from_submilestone_id) === "undefined") {
                                sort();
                            } else if (typeof(to_submilestone_id) === "undefined") {
                                updateArtifactlink("unassociate-artifact-to", from_submilestone_id, sort)
                            } else if (typeof(from_submilestone_id) === "undefined") {
                                updateArtifactlink("associate-artifact-to", to_submilestone_id, sort)
                            } else {
                                updateArtifactlink("unassociate-artifact-to", from_submilestone_id, sort)
                                updateArtifactlink("associate-artifact-to", to_submilestone_id)
                            }
                        }

                        function sort() {
                            if (next_id) {
                                sortHigher(item_id, next_id);
                            } else if (prev_id) {
                                sortLesser(item_id, prev_id);
                            }
                        }

                        function updateArtifactlink(func, submilestone_id, callback) {
                             $.ajax({
                                url  : codendi.tracker.base_url,
                                data : {
                                    "func"              : func,
                                    "aid"               : submilestone_id,
                                    "linked-artifact-id": item_id
                                },
                                method : "get",
                                success : function() {
                                    if (typeof(callback) === "function") {
                                        callback();
                                    }
                                }
                            });
                        }

                        function sortHigher(source_id, target_id) {
                            updateOrder('higher-priority-than', source_id, target_id);
                        }

                        function sortLesser(source_id, target_id) {
                            updateOrder('lesser-priority-than', source_id, target_id);
                        }

                        function updateOrder(func, source_id, target_id) {
                            $.ajax({
                                url  : codendi.tracker.base_url,
                                data : {
                                    "func"             : func,
                                    "aid"              : source_id,
                                    "target-id"        : target_id
                                },
                                method : "get"
                            });
                        }
                }
            }).disableSelection();

            self.establishWidthOfCellsToBeConstitentWhileDragging($submilestone_element_rows);
        }
    });
})(jQuery);
