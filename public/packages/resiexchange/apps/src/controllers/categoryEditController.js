angular.module('resiexchange')

.controller('categoryEditController', [
    'category', 
    '$scope', 
    '$window', 
    '$location', 
    'feedbackService', 
    'actionService',
    '$http',
    '$httpParamSerializerJQLike',    
    function(category, $scope, $window, $location, feedbackService, actionService, $http, $httpParamSerializerJQLike) {
        console.log('categoryEdit controller');
        
        var ctrl = this;   
       
        // @model
        $scope.category = category;
        
        $scope.loadMatches = function(query) {
            if(query.length < 2) return [];
            
            return $http.get('index.php?get=resiway_category_list&order=title&'+$httpParamSerializerJQLike({channel: global_config.channel, domain: ['title', 'ilike', '%'+query+'%']}))
            .then(
                function successCallback(response) {
                    var data = response.data;
                    if(typeof data.result != 'object') return [];
                    return data.result;
                },
                function errorCallback(response) {
                    // something went wrong server-side
                    return [];
                }
            );                
        };
        
        // @events
        $scope.$watch('category.parent', function() {
            $scope.category.parent_id = $scope.category.parent.id;   
        });

        // set initial parent 
        $scope.category.parent = { id: category.parent_id, title: category['parent_id.title'], path: category['parent_id.path']};
                
        // @methods
        $scope.categoryPost = function($event) {
            var selector = feedbackService.selector(angular.element($event.target));                   
            actionService.perform({
                // valid name of the action to perform server-side
                action: 'resiway_category_edit',
                // string representing the data to submit to action handler (i.e.: serialized value of a form)
                data: {
                    category_id: $scope.category.id,
                    title: $scope.category.title,
                    description: $scope.category.description,
                    parent_id: $scope.category.parent_id
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
                            msg = 'category_'+msg;
                        }                        
                        feedbackService.popover(selector, msg);
                    }
                    else {
                        $location.path('/categories');
                    }
                }        
            });
        };  
           
    }
]);