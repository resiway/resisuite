angular.module('resiexchange')

.controller('answerEditController', [
    'answer', 
    '$scope', 
    '$window', 
    '$location', 
    '$sce', 
    'feedbackService', 
    'actionService', 
    'textAngularManager',
    function(answer, $scope, $window, $location, $sce, feedbackService, actionService, textAngularManager) {
        console.log('answerEdit controller');
        
        var ctrl = this;   
      
        // @model
        $scope.answer = answer;
        
        // @methods
        $scope.answerPost = function($event) {
            var selector = feedbackService.selector($event.target);
            actionService.perform({
                // valid name of the action to perform server-side
                action: 'resiexchange_answer_edit',
                // string representing the data to submit to action handler (i.e.: serialized value of a form)
                data: {
                    answer_id: $scope.answer.id,
                    content: $scope.answer.content
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
                        if(msg.substr(0, 8) == 'missing_') {
                            msg = 'answer_'+msg;
                        }                             
                        feedbackService.popover(selector, msg);
                    }
                    else {
                        var question_id = data.result.question_id;
                        $location.path('/question/'+question_id);
                    }
                }        
            });
        };     
    }
]);