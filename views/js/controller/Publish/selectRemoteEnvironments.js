/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020  (original work) Open Assessment Technologies SA;
 */

define([
    'jquery',
    'i18n',
    'layout/actions',
    'ui/feedback',
    'ui/taskQueue/taskQueue',
    'layout/loading-bar'
], function (
    $,
    __,
    actionManager,
    feedback,
    taskQueue,
    loadingBar
) {
    'use strict';

    /**
     * wrapped the old jstree API used to refresh the tree and optionally select a resource
     * @param {String} [uriResource] - the uri resource node to be selected
     */
    const refreshTree = function refreshTree(uriResource) {
        actionManager.trigger('refresh', {
            uri: uriResource
        });
    };

    const initCheckboxes = function initCheckboxes() {
        const $form = $('#publish-remote');
        const $checkboxes = $('input[name="remote-environments[]"]', $form).not(':disabled');
        const $ul = $('ul', $form);
        if ($checkboxes.length) {
            const $checkAll = $('<input type="checkbox" id="all-env" title="'
                + __('All active environments') + '">');

            $checkAll.off('click').on('click', function (){
                $checkboxes.prop('checked', $(this).prop('checked'));
            });

            $checkAll.insertBefore($ul);
            $('<label for="all-env"><b>' + __('All active environments') + '</b></label>').insertBefore($ul);
        }
    };

    const redeclarePublishBtn = function redeclarePublishBtn() {
        const $publishBtn = $('#publish-to-remote');
        const $form = $('#publish-remote');
        const $treePublishButton = $('#delivery-remote-publish');

        $form.on('submit', function (e) {
            e.preventDefault();

            loadingBar.start();
            taskQueue.pollAllStop();
            taskQueue
                .create($form.prop('action'), $form.serializeArray())
                .then(function(result) {
                    const tasksCount = result['extra']['allTasks'].length + 1;
                    const message = __('<strong> %s </strong> task(s) have been moved to the background.', tasksCount);

                    feedback(null, {
                        encodeHtml: false,
                        timeout: { info: 8000 }
                    }).info(message);

                    // updating tasks in the background tasks
                    taskQueue.pollAll(true);
                    refreshTree($('#selected-delivery-uri').val());
                    loadingBar.stop();
                }).catch(function(err) {
                //in case of error display it and continue task queue activity
                taskQueue.pollAll();
                loadingBar.stop();
                //format and display error message to user
                feedback().error(err.message);
                // refreshTree();
                $treePublishButton.click();
            });

            return false;
        });
        $publishBtn.removeClass('hidden');
    };

    return {
        start: function start() {
            initCheckboxes();
            redeclarePublishBtn();
        }
    }
});
