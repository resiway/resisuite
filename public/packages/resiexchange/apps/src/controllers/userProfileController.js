angular.module('resiexchange')

.controller('userProfileController', [
    'user', 
    '$scope', 
    '$http', 
    function(user, $scope, $http) {
        console.log('userProfile controller');
        
        var ctrl = this;
        
        ctrl.user = user;
        
        
        // @init
        // acknowledge user profile view (so far, user data have been loaded but nothing indicated a profile view)
        $http.get('index.php?do=resiway_user_profileview&id='+user.id);

        
        ctrl.load = function(config) {
            if(config.currentPage != config.previousPage) {
                config.previousPage = config.currentPage;
                // reset objects list (triggers loader display)
                config.items = -1;          
                $http.post('index.php?get='+config.provider, {
                    domain: config.domain,
                    start: (config.currentPage-1)*config.limit,
                    limit: config.limit,
                    total: config.total
                }).then(
                function successCallback(response) {
                    var data = response.data;
                    config.items = data.result;
                    config.total = data.total;
                },
                function errorCallback() {
                    // something went wrong server-side
                });
            }
        };
        
        angular.merge(ctrl, {
            updates: {
                items: -1,
                total: -1,
                currentPage: 1,
                previousPage: -1,
                limit: 5,
                domain: [[['user_id', '=', ctrl.user.id],['user_increment','<>', 0]],[['author_id', '=', ctrl.user.id],['author_increment','<>', 0]]],
                provider: 'resiway_actionlog_list'
            },
            badges: {
                items: -1,
                total: -1,
                currentPage: 1,
                previousPage: -1,                
                limit: 5,
                domain: ['user_id', '=', ctrl.user.id],
                provider: 'resiway_userbadge_list'
            },            
            questions: {
                items: -1,
                total: -1,
                currentPage: 1,
                previousPage: -1,                
                limit: 5,
                domain: ['creator', '=', ctrl.user.id],
                provider: 'resiexchange_question_list'
            },
            answers: {
                items: -1,
                total: -1,
                currentPage: 1,
                previousPage: -1,                
                limit: 5,
                domain: ['creator', '=', ctrl.user.id],
                provider: 'resiexchange_answer_list'
            },
            favorites: {
                items: -1,
                total: -1,
                currentPage: 1,
                previousPage: -1,                
                limit: 5,
                // 'resiexchange_question_star' == action (id=4)
                domain: [['user_id', '=', ctrl.user.id], ['action_id','=','4']],
                provider: 'resiway_actionlog_list'
            },
            actions: {
                items: -1,
                total: -1,
                currentPage: 1,
                previousPage: -1,                
                limit: 5,
                domain: [['user_id', '=', ctrl.user.id]],
                provider: 'resiway_actionlog_list'
            },        
        });   
    }
]);