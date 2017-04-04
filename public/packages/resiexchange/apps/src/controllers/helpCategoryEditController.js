angular.module('resiexchange')

.controller('helpCategoryEditController', [
    'category', 
    '$scope',
    '$location',
    'feedbackService',
    'actionService',
    function(category, $scope, $location, feedbackService, actionService) {
        console.log('helpCategoryEdit controller');

        var ctrl = this;

        // @data model
        ctrl.category = angular.extend({
                            title: '', 
                            description: ''
                        }, 
                        category);

        // @methods
        $scope.categoryPost = function($event) {
            var selector = feedbackService.selector($event.target);
            actionService.perform({
                // valid name of the action to perform server-side
                action: 'resiexchange_helpcategory_edit',
                // string representing the data to submit to action handler (i.e.: serialized value of a form)
                data: {
                    category_id: ctrl.category.id,
                    title: ctrl.category.title,
                    description: ctrl.category.description
                },
                // scope in wich callback function will apply 
                scope: $scope,
                // callback function to run after action completion (to handle error cases, ...)
                callback: function($scope, data) {
                    // we need to do it this way because current controller might be destroyed in the meantime
                    // (if route is changed to signin form)
                    if(typeof data.result != 'object') {
                        // result is an error code
                        var error_id = data.error_message_ids[0];                    
                        // todo : get error_id translation
                        var msg = error_id;
                        feedbackService.popover(selector, msg);
                    }
                    else {
                        var category_id = data.result.id;
                        $location.path('/help/category/'+category_id);
                    }
                }        
            });
        };          
        
    }
]);