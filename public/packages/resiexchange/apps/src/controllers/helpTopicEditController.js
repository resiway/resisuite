angular.module('resiexchange')

.controller('helpTopicEditController', [
    'topic',
    'categories', 
    '$scope',
    '$location',
    '$sce',
    'feedbackService',
    'actionService',
    function(topic, categories, $scope, $location, $sce, feedbackService, actionService) {
        console.log('hepTopicEdit controller');

        var ctrl = this;

                // content is inside a textarea and do not need sanitize check
        topic.content = $sce.valueOf(topic.content);
        
        // @data model
        ctrl.topic = angular.extend({
                        id: 0,
                        title: '',
                        content: '',
                        category_id: 0
                     }, 
                     topic);
       
        ctrl.categories = categories;

        $scope.category = null;
        
        // set initial parent 
        angular.forEach(ctrl.categories, function(category, index) {
            if(category.id == ctrl.topic.category_id) {
                $scope.category = category; 
            }
        });       
        
        // @events
        $scope.$watch('category', function() {
            ctrl.topic.category_id = $scope.category.id;   
        });
        
        // @methods
        $scope.topicPost = function($event) {
            var selector = feedbackService.selector($event.target);
            actionService.perform({
                // valid name of the action to perform server-side
                action: 'resiexchange_helptopic_edit',
                // string representing the data to submit to action handler (i.e.: serialized value of a form)
                data: {
                    topic_id: ctrl.topic.id,
                    title: ctrl.topic.title,
                    content: ctrl.topic.content,
                    category_id: ctrl.topic.category_id
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
                        var topic_id = data.result.id;
                        $location.path('/help/topic/'+topic_id);
                    }
                }        
            });
        };          
    }
]);