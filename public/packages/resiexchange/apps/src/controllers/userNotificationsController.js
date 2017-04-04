angular.module('resiexchange')

.controller('userNotificationsController', [ 
    '$scope', 
    '$rootScope', 
    'actionService', 
    'feedbackService', 
    function($scope, $rootScope, action, feedback) {
    console.log('userNotifications controller');
    
    var ctrl = this;
    
    ctrl.dismiss = function($event, index) {
        var selector = feedback.selector($event.target);         
        action.perform({
            // valid name of the action to perform server-side
            action: 'resiway_notification_dismiss',
            // string representing the data to submit to action handler (i.e.: serialized value of a form)
            data: {
                notification_id: $rootScope.user.notifications[index].id
            },
            // scope in wich callback function will apply 
            scope: $scope,
            // callback function to run after action completion (to handle error cases, ...)
            callback: function($scope, data) {
                // we need to do it this way because current controller might be destroyed in the meantime
                // (if route is changed to signin form)
                if(data.result === true) {
                    $rootScope.user.notifications.splice(index, 1); 
                }
                else {
                    // result is an error code
                    var error_id = data.error_message_ids[0];                    
                    // todo : get error_id translation
                    var msg = error_id;
                    feedback.popover(selector, msg);                    
                }
            }        
        });        
    };
}]);