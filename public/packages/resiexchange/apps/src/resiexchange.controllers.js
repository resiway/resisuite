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
angular.module('resiexchange')

.controller('badgesController', [
    'categories', 
    '$scope',
    '$http',
    'authenticationService',    
    function(categories, $scope, $http, authenticationService) {
        console.log('badges controller');

        var ctrl = this;


        
        // @init
        // group badges inside each category
        angular.forEach(categories, function(category, i) {
            categories[i].groups = {};
            angular.forEach(category.badges, function(badge, j) {
                if(typeof categories[i].groups[badge.group] == 'undefined') {
                    categories[i].groups[badge.group] = [];
                }
                categories[i].groups[badge.group].push(badge);                
            });
        });

        // request current user badges
        authenticationService.userId().then(
            function(user_id) {
            $http.post('index.php?get=resiway_userbadge_list', {
                domain: ['user_id', '=', user_id],
                start: 0,
                limit: 100
            }).then(
            function successCallback(response) {
                var data = response.data;
                angular.forEach(data.result, function (badge, i) {
                    $scope.userBadges.push(badge.badge_id);
                });
            });
        });         
  
        
        // @data model
        $scope.userBadges = [];
        $scope.badgeCategories = categories;
        
      
    }
]);
angular.module('resiexchange')

.controller('categoriesController', [
    'categories', 
    '$scope',
    '$http',
    function(categories, $scope, $http) {
        console.log('categories controller');

        var ctrl = this;

        // @data model
        ctrl.config = {
            items: categories,
            total: -1,
            currentPage: 1,
            previousPage: -1,
            limit: 30,
            domain: [],
            loading: false
        };
        
        ctrl.load = function(config) {
            if(config.currentPage != config.previousPage) {
                config.previousPage = config.currentPage;
                // trigger loader display
                if(config.total > 0) {
                    config.loading = true;
                }
                $http.post('index.php?get=resiway_category_list', {
                    domain: config.domain,
                    start: (config.currentPage-1)*config.limit,
                    limit: config.limit,
                    total: config.total
                }).then(
                function successCallback(response) {
                    var data = response.data;
                    config.items = data.result;
                    config.total = data.total;
                    config.loading = false;
                },
                function errorCallback() {
                    // something went wrong server-side
                });
            }
        };
        
        
        // @init
        ctrl.load(ctrl.config);
        
    }
]);
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
angular.module('resiexchange')

.controller('emptyController', [
    '$scope',
    function($scope) {
        console.log('empty controller');

        var ctrl = this;
        
    }
]);
angular.module('resiexchange')

.controller('helpCategoriesController', [
    'categories', 
    '$scope',
    function(categories, $scope) {
        console.log('helpCategories controller');

        var ctrl = this;

        // @data model
        ctrl.categories = categories;
    
    }
]);
angular.module('resiexchange')

.controller('helpCategoryController', [
    'category', 
    '$scope',
    function(category, $scope) {
        console.log('helpCategory controller');

        var ctrl = this;

        // @data model
        ctrl.category = category;
    
    }
]);
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
angular.module('resiexchange')

.controller('helpTopicController', [
    'topic', 
    'categories',     
    '$scope',
    function(topic, categories, $scope) {
        console.log('helpTopic controller');

        var ctrl = this;

        // @data model
        ctrl.topic = topic;
        ctrl.categories = categories;
    
    }
]);
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
angular.module('resiexchange')

.controller('homeController', ['$http', '$rootScope', function($http, $rootScope) {
    console.log('home controller');  
    
    var ctrl = this;

    ctrl.questions = [];
    
    $http.get('index.php?get=resiexchange_stats')
    .then(
    function successCallback(response) {
        var data = response.data;
        if(typeof response.data.result == 'object') {
            ctrl.count_questions = data.result['resiexchange.count_questions'];
            ctrl.count_answers = data.result['resiexchange.count_answers'];
            ctrl.count_comments = data.result['resiexchange.count_comments'];
            ctrl.count_users = data.result['resiway.count_users'];            
        }
    },
    function errorCallback() {
        // something went wrong server-side
    }); 

    $http.get('index.php?get=resiexchange_question_list&order=score&limit=5&sort=desc')
    .then(
    function successCallback(response) {
        var data = response.data;
        if(typeof response.data.result == 'object') {
            ctrl.questions = response.data.result;
        }
    },
    function errorCallback() {
        // something went wrong server-side
    });     
}]);
angular.module('resiexchange')

/**
 * Question controller
 *
 */
.controller('questionController', [
    'question', 
    '$scope', 
    '$window', 
    '$location',
    '$http',    
    '$sce', 
    '$timeout', 
    '$uibModal', 
    'actionService', 
    'feedbackService', 
    'textAngularManager',
    function(question, $scope, $window, $location, $http, $sce, $timeout, $uibModal, actionService, feedbackService, textAngularManager) {
        console.log('question controller');
        
        var ctrl = this;

        // @model
        $scope.question = question;

        
        /*
        * async load and inject $scope.related_questions
        */
        $scope.related_questions = [];
        $http.get('index.php?get=resiexchange_question_related&question_id='+question.id)
        .then(
            function (response) {
                $scope.related_questions = response.data.result;
            }
        );


        
    // todo : move this to rootScope
        ctrl.open = function (title_id, header_id, content) {
            return $uibModal.open({
                animation: true,
                ariaLabelledBy: 'modal-title',
                ariaDescribedBy: 'modal-body',
                templateUrl: 'modalCustom.html',
                controller: ['$uibModalInstance', function ($uibModalInstance, items) {
                    var ctrl = this;
                    ctrl.title_id = title_id;
                    ctrl.header_id = header_id;
                    ctrl.body = content;
                    
                    ctrl.ok = function () {
                        $uibModalInstance.close();
                    };
                    ctrl.cancel = function () {
                        $uibModalInstance.dismiss();
                    };
                }],
                controllerAs: 'ctrl', 
                size: 'md',
                appendTo: angular.element(document.querySelector(".modal-wrapper")),
                resolve: {
                    items: function () {
                      return ctrl.items;
                    }
                }
            }).result;
        };
           

        // @methods
        $scope.begin = function (commit, previous) {
            $scope.committed = false;
            // make a copy of previous state
            $scope.previous = angular.merge({}, previous);
            // commit transaction (can be rolled back to previous state if something goes wrong)
            commit($scope);
            // prevent further commits (commit functions are in charge of checking this var)
            $scope.committed = true;
        };
        
        $scope.rollback = function () {
            if(angular.isDefined($scope.previous) && typeof $scope.previous == 'object') {
                angular.merge($scope.question, $scope.previous);
            }
        };
        
        $scope.questionComment = function($event) {

            // remember selector for popover location 
            var selector = feedbackService.selector($event.target);
            
            actionService.perform({
                // valid name of the action to perform server-side
                action: 'resiexchange_question_comment',
                // string representing the data to submit to action handler (i.e.: serialized value of a form)
                data: {
                    question_id: $scope.question.id,
                    content: $scope.question.newCommentContent
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
                        var comment_id = data.result.id;
                        // add new comment to the list
                        $scope.question.comments.push(data.result);
                        $scope.question.newCommentShow = false;
                        $scope.question.newCommentContent = '';
                        // wait for next digest cycle
                        $timeout(function() {
                            // scroll to newly created comment
                            feedbackService.popover('#comment-'+comment_id, '');
                        });
                    }
                }        
            });
        };

        $scope.questionFlag = function ($event) {

            // define transaction
            var commit = function ($scope) {
                // prevent action if it has already been committed
                if(!angular.isDefined($scope.committed) || !$scope.committed) {
                    // make sure impacted properties are set
                    if(!angular.isDefined($scope.question.history['resiexchange_question_flag'])) {
                        $scope.question.history['resiexchange_question_flag'] = false;
                    }
                    // update current state to new values
                    if($scope.question.history['resiexchange_question_flag'] === true) {
                        $scope.question.history['resiexchange_question_flag'] = false;
                    }
                    else {
                        $scope.question.history['resiexchange_question_flag'] = true;
                    }
                }
            };

            // set previous state and begin transaction
            $scope.begin(commit, 
                         { 
                            history: {
                                resiexchange_question_flag: $scope.question.history['resiexchange_question_flag'] 
                            }
                         });     
            
            // remember selector for popover location        
            var selector = feedbackService.selector($event.target);
            
            actionService.perform({
                // valid name of the action to perform server-side
                action: 'resiexchange_question_flag',
                // string representing the data to submit to action handler (i.e.: serialized value of a form)
                data: {
                        question_id: $scope.question.id
                },
                // scope in wich callback function will apply 
                scope: $scope,
                // callback function to run after action completion (to handle error cases, ...)
                callback: function($scope, data) {
                    // we need to do it this way because current controller might be destroyed in the meantime
                    // toggle related entries in current history
                    if(data.result < 0) {
                        // rollback
                        $scope.rollback();
                        
                        // result is an error code
                        var error_id = data.error_message_ids[0];                    
                        // todo : get error_id translation
                        var msg = error_id;
                        feedbackService.popover(selector, msg);                    
                    }                
                    else {
                        commit($scope);
                        // $scope.question.history['resiexchange_question_flag'] = data.result;
                    }
                }        
            });
        };

        $scope.questionAnswer = function($event) {

            // remember selector for popover location 
            var selector = feedbackService.selector($event.target);                   
            
            actionService.perform({
                // valid name of the action to perform server-side
                action: 'resiexchange_question_answer',
                // string representing the data to submit to action handler (i.e.: serialized value of a form)
                data: {
                    question_id: $scope.question.id,
                    content: $scope.question.newAnswerContent
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
                        var answer_id = data.result.id;
                        // mark html as safe
                        data.result.content = $sce.trustAsHtml(data.result.content);
                        
                        // add special fields
                        data.result.commentsLimit = 5;
                        data.result.newCommentShow = false;
                        data.result.newCommentContent = '';
                        
                        // add new answer to the list
                        $scope.question.answers.push(data.result);
                        // hide user-answer block
                        $scope.question.history['resiexchange_question_answer'] = true;
                        // wait for next digest cycle
                        $timeout(function() {
                            // scroll to newly created answer
                            feedbackService.popover('#answer-'+answer_id, '');
                        });                    
                    }
                }        
            });
        };  
        
        $scope.questionVoteUp = function ($event) {            
            
            // define transaction
            var commit = function ($scope) {
                // prevent action if it has already been committed
                if(!angular.isDefined($scope.committed) || !$scope.committed) {                
                    // make sure impacted properties are set
                    if(!angular.isDefined($scope.question.history['resiexchange_question_votedown'])) {
                        $scope.question.history['resiexchange_question_votedown'] = false;
                    }
                    if(!angular.isDefined($scope.question.history['resiexchange_question_voteup'])) {
                        $scope.question.history['resiexchange_question_voteup'] = false;
                    }                    
                    // update current state to new values
                    if($scope.question.history['resiexchange_question_voteup'] === true) {
                        // toggle voteup
                        $scope.question.history['resiexchange_question_voteup'] = false;
                        $scope.question.score--;
                    }
                    else {
                        // undo votedown
                        if($scope.question.history['resiexchange_question_votedown'] === true) {
                            $scope.question.history['resiexchange_question_votedown'] = false;
                            $scope.question.score++;
                        }
                        // voteup
                        $scope.question.history['resiexchange_question_voteup'] = true;
                        $scope.question.score++;
                    }
                }
            };

            // set previous state and begin transaction
            $scope.begin(commit, 
                         {
                            history: {
                                resiexchange_question_votedown: $scope.question.history['resiexchange_question_votedown'],
                                resiexchange_question_voteup:   $scope.question.history['resiexchange_question_voteup']                        
                            },
                            score: $scope.question.score
                         });
                         
            // remember selector for popover location    
            var selector = feedbackService.selector($event.target);
            
            actionService.perform({
                // valid name of the action to perform server-side
                action: 'resiexchange_question_voteup',
                // string representing the data to submit to action handler (i.e.: serialized value of a form)
                data: {question_id: $scope.question.id},
                // scope in wich callback function will apply 
                scope: $scope,
                // callback function to run after action completion (to handle error cases, ...)
                callback: function($scope, data) {
                    // we need to do it this way because current controller might be destroyed in the meantime
                    // (if route is changed to signin form)
                    if(data.result >= 0) {
                        // commit if it hasn't been done already
                        commit($scope);
                        if(data.result === true) feedbackService.popover(selector, 'QUESTION_ACTIONS_VOTEUP_OK', 'info', true);
                        // $scope.question.history['resiexchange_question_voteup'] = true;
                        // $scope.question.score++;
                    }
                    else {
                        // rollback
                        $scope.rollback();
                        
                        // result is an error code
                        var error_id = data.error_message_ids[0];                    
                        // todo : get error_id translation
                        var msg = error_id;
                        
                        feedbackService.popover(selector, msg);

                    }
                }        
            });
        };
        
        $scope.questionVoteDown = function ($event) {
            
            // define transaction
            var commit = function ($scope) {
                // prevent action if it has already been committed
                if(!angular.isDefined($scope.committed) || !$scope.committed) {                
                    // make sure impacted properties are set
                    if(!angular.isDefined($scope.question.history['resiexchange_question_votedown'])) {
                        $scope.question.history['resiexchange_question_votedown'] = false;
                    }
                    if(!angular.isDefined($scope.question.history['resiexchange_question_voteup'])) {
                        $scope.question.history['resiexchange_question_voteup'] = false;
                    }                    
                    // update current state to new values
                    if($scope.question.history['resiexchange_question_votedown'] === true) {
                        // toggle votedown
                        $scope.question.history['resiexchange_question_votedown'] = false;
                        $scope.question.score--;
                    }
                    else {
                        // undo voteup
                        if($scope.question.history['resiexchange_question_voteup'] === true) {
                            $scope.question.history['resiexchange_question_voteup'] = false;
                            $scope.question.score--;
                        }
                        // votedown
                        $scope.question.history['resiexchange_question_votedown'] = true;
                        $scope.question.score--;
                    }
                }
            };

            // set previous state and begin transaction
            $scope.begin(commit, 
                         {
                            history: {
                                resiexchange_question_votedown: $scope.question.history['resiexchange_question_votedown'],
                                resiexchange_question_voteup:   $scope.question.history['resiexchange_question_voteup']                        
                            },
                            score: $scope.question.score
                         });
                         
            // remember selector for popover location
            var selector = feedbackService.selector($event.target);
            
            actionService.perform({
                // valid name of the action to perform server-side
                action: 'resiexchange_question_votedown',
                // string representing the data to submit to action handler (i.e.: serialized value of a form)
                data: {question_id: $scope.question.id},
                // scope in wich callback function will apply 
                scope: $scope,
                // callback function to run after action completion (to handle error cases, ...)
                callback: function($scope, data) {
                    // we need to do it this way because current controller might be destroyed in the meantime
                    // toggle related entries in current history
                    if(data.result >= 0) {
                        // commit if it hasn't been done already
                        commit($scope);
                    }
                    else {
                        // rollback
                        $scope.rollback();
                        
                        // result is an error code
                        var error_id = data.error_message_ids[0];                    
                        // todo : get error_id translation
                        var msg = error_id;
                        feedbackService.popover(selector, msg);                    
                    }
                }        
            });
        };    

        $scope.questionStar = function ($event) {

            // define transaction
            var commit = function ($scope) {
                // prevent action if it has already been committed
                if(!angular.isDefined($scope.committed) || !$scope.committed) {
                    // make sure impacted properties are set
                    if(!angular.isDefined($scope.question.history['resiexchange_question_star'])) {
                        $scope.question.history['resiexchange_question_star'] = false;
                    }
                    // update current state to new values
                    if($scope.question.history['resiexchange_question_star'] === true) {
                        $scope.question.history['resiexchange_question_star'] = false;
                        $scope.question.count_stars--;
                    }
                    else {
                        $scope.question.history['resiexchange_question_star'] = true;
                        $scope.question.count_stars++;
                    }
                }
            };

            // set previous state and begin transaction
            $scope.begin(commit, 
                         { 
                            history: {
                                resiexchange_question_star: $scope.question.history['resiexchange_question_star']
                            },
                            count_stars: $scope.question.count_stars            
                         });    
            
            // remember selector for popover location
            var selector = feedbackService.selector($event.target);
            
            actionService.perform({
                // valid name of the action to perform server-side
                action: 'resiexchange_question_star',
                // string representing the data to submit to action handler (i.e.: serialized value of a form)
                data: {question_id: $scope.question.id},
                // scope in wich callback function will apply 
                scope: $scope,
                // callback function to run after action completion (to handle error cases, ...)
                callback: function($scope, data) {
                    // we need to do it this way because current controller might be destroyed in the meantime
                    // toggle related entries in current history
                    if(data.result < 0) {
                        // rollback
                        $scope.rollback();
                        
                        // result is an error code
                        var error_id = data.error_message_ids[0];                    
                        // todo : get error_id translation
                        var msg = error_id;
                        feedbackService.popover(selector, msg);                    
                    }                
                    else {
                        // commit if it hasn't been done already
                        commit($scope);
                        /*
                        $scope.question.history['resiexchange_question_star'] = data.result;
                        if(data.result === true) {
                            $scope.question.count_stars++;
                        }
                        else {
                            $scope.question.count_stars--;
                        }
                        */
                    }
                }        
            });
        };      

        $scope.questionCommentVoteUp = function ($event, index) {

            // define transaction
            var commit = function ($scope) {
                // prevent action if it has already been committed
                if(!angular.isDefined($scope.committed) || !$scope.committed) {                    
                    // make sure impacted properties are set
                    if(!angular.isDefined($scope.question.comments[index].history['resiexchange_questioncomment_voteup'])) {
                        $scope.question.comments[index].history['resiexchange_questioncomment_voteup'] = false;
                    }                    
                    // update current state to new values
                    if($scope.question.comments[index].history['resiexchange_questioncomment_voteup'] === true) {
                        $scope.question.comments[index].history['resiexchange_questioncomment_voteup'] = false;
                        $scope.question.comments[index].score--;
                    }
                    else {
                        $scope.question.comments[index].history['resiexchange_questioncomment_voteup'] = true;
                        $scope.question.comments[index].score++;
                    }
                }
            };
            
            // set previous state and begin transaction
            $scope.begin(commit, { comments: $scope.question.comments });
            
            // remember selector for popover location            
            var selector = feedbackService.selector($event.target);
            
            actionService.perform({
                // valid name of the action to perform server-side
                action: 'resiexchange_questioncomment_voteup',
                // string representing the data to submit to action handler (i.e.: serialized value of a form)
                data: {
                        comment_id: $scope.question.comments[index].id
                },
                // scope in wich callback function will apply 
                scope: $scope,
                // callback function to run after action completion (to handle error cases, ...)
                callback: function($scope, data) {
                    // we need to do it this way because current controller might be destroyed in the meantime
                    // toggle related entries in current history
                    if(data.result < 0) {
                        // rollback transaction
                        $scope.rollback();
                        
                        // result is an error code
                        var error_id = data.error_message_ids[0];                    
                        // todo : get error_id translation
                        var msg = error_id;
                        feedbackService.popover(selector, msg);                    
                    }                
                    else {
                        // commit if it hasn't been done already
                        commit($scope);
                        /*
                        $scope.question.comments[index].history['resiexchange_questioncomment_voteup'] = data.result;
                        if(data.result === true) {
                            $scope.question.comments[index].score++;
                        }
                        else {
                            $scope.question.comments[index].score--;
                        }
                        */
                    }
                }        
            });
        };
        
        $scope.questionDelete = function ($event) {
            
            // remember selector for popover location 
            var selector = feedbackService.selector($event.target);
            
            ctrl.open('MODAL_QUESTION_DELETE_TITLE', 'MODAL_QUESTION_DELETE_HEADER', $scope.question.title)
            .then(
                function () {
                    actionService.perform({
                        // valid name of the action to perform server-side
                        action: 'resiexchange_question_delete',
                        // string representing the data to submit to action handler (i.e.: serialized value of a form)
                        data: {question_id: $scope.question.id},
                        // scope in wich callback function will apply 
                        scope: $scope,
                        // callback function to run after action completion (to handle error cases, ...)
                        callback: function($scope, data) {
                            // we need to do it this way because current controller might be destroyed in the meantime
                            // (if route is changed to signin form)
                            if(data.result === true) {                  
                                // go back to questions list
                                $location.path('/questions');
                            }
                            else if(data.result === false) { 
                                // deletion toggle : we shouldn't reach this point with this controller
                            }
                            else {
                                // result is an error code
                                var error_id = data.error_message_ids[0];                    
                                // todo : get error_id translation
                                var msg = error_id;
                                feedbackService.popover(selector, msg);
                            }
                        }        
                    });
                }
            );     
        };
        
        $scope.answerVoteUp = function ($event, index) {
               
            // define transaction
            var commit = function ($scope) {
                // prevent action if it has already been committed
                if(!angular.isDefined($scope.committed) || !$scope.committed) {
                    // make sure impacted properties are set
                    if(!angular.isDefined($scope.question.answers[index].history['resiexchange_answer_votedown'])) {
                        $scope.question.answers[index].history['resiexchange_answer_votedown'] = false;
                    }
                    if(!angular.isDefined($scope.question.answers[index].history['resiexchange_answer_voteup'])) {
                        $scope.question.answers[index].history['resiexchange_answer_voteup'] = false;
                    }
                    // update current state to new values
                    if($scope.question.answers[index].history['resiexchange_answer_voteup'] === true) {
                        // toggle voteup
                        $scope.question.answers[index].history['resiexchange_answer_voteup'] = false;
                        $scope.question.answers[index].score--;
                    }
                    else {
                        // undo votedown
                        if($scope.question.answers[index].history['resiexchange_answer_votedown'] === true) {
                            $scope.question.answers[index].history['resiexchange_answer_votedown'] = false;
                            $scope.question.answers[index].score++;
                        }
                        // voteup
                        $scope.question.answers[index].history['resiexchange_answer_voteup'] = true;
                        $scope.question.answers[index].score++;
                    }
                }
            };

            // set previous state and begin transaction
            $scope.begin(commit, { answers: $scope.question.answers });

            // remember selector for popover location             
            var selector = feedbackService.selector($event.target);
            
            actionService.perform({
                // valid name of the action to perform server-side
                action: 'resiexchange_answer_voteup',
                // string representing the data to submit to action handler (i.e.: serialized value of a form)
                data: {answer_id: $scope.question.answers[index].id},
                // scope in wich callback function will apply 
                scope: $scope,
                // callback function to run after action completion (to handle error cases, ...)
                callback: function($scope, data) {
                    // we need to do it this way because current controller might be destroyed in the meantime
                    // (if route is changed to signin form)
                    if(data.result >= 0) {
                        // commit if it hasn't been done already
                        commit($scope);
                        if(data.result === true) feedbackService.popover(selector, 'QUESTION_ACTIONS_VOTEUP_OK', 'info', true);
                    }
                    else {
                        // rollback
                        $scope.rollback();
                        
                        // result is an error code
                        var error_id = data.error_message_ids[0];                    
                        // todo : get error_id translation
                        var msg = error_id;
                        feedbackService.popover(selector, msg);
                    }
                }        
            });
        };
        
        $scope.answerVoteDown = function ($event, index) {

            // define transaction
            var commit = function ($scope) {
                // prevent action if it has already been committed
                if(!angular.isDefined($scope.committed) || !$scope.committed) {
                    // make sure impacted properties are set
                    if(!angular.isDefined($scope.question.answers[index].history['resiexchange_answer_votedown'])) {
                        $scope.question.answers[index].history['resiexchange_answer_votedown'] = false;
                    }
                    if(!angular.isDefined($scope.question.answers[index].history['resiexchange_answer_voteup'])) {
                        $scope.question.answers[index].history['resiexchange_answer_voteup'] = false;
                    }
                    // update current state to new values
                    if($scope.question.answers[index].history['resiexchange_answer_votedown'] === true) {
                        // toggle votedown
                        $scope.question.answers[index].history['resiexchange_answer_votedown'] = false;
                        $scope.question.answers[index].score++;
                    }
                    else {
                        // undo voteup
                        if($scope.question.answers[index].history['resiexchange_answer_voteup'] === true) {
                            $scope.question.answers[index].history['resiexchange_answer_voteup'] = false;
                            $scope.question.answers[index].score--;                            
                        }
                        // votedown
                        $scope.question.answers[index].history['resiexchange_answer_votedown'] = true;
                        $scope.question.answers[index].score--;
                    }
                }
            };

            // set previous state and begin transaction
            $scope.begin(commit, { answers: $scope.question.answers });

            // remember selector for popover location              
            var selector = feedbackService.selector($event.target);
            
            actionService.perform({
                // valid name of the action to perform server-side
                action: 'resiexchange_answer_votedown',
                // string representing the data to submit to action handler (i.e.: serialized value of a form)
                data: {answer_id: $scope.question.answers[index].id},
                // scope in wich callback function will apply 
                scope: $scope,
                // callback function to run after action completion (to handle error cases, ...)
                callback: function($scope, data) {
                    // we need to do it this way because current controller might be destroyed in the meantime
                    // toggle related entries in current history
                    if(data.result >= 0) {                  
                        commit($scope);                        
                    }
                    else {
                        // result is an error code
                        var error_id = data.error_message_ids[0];                    
                        // todo : get error_id translation
                        var msg = error_id;
                        feedbackService.popover(selector, msg);
                    }
                }        
            });
        };      
        
        $scope.answerFlag = function ($event, index) {

            // define transaction
            var commit = function ($scope) {
                // prevent action if it has already been committed
                if(!angular.isDefined($scope.committed) || !$scope.committed) {
                    // make sure impacted properties are set
                    if(!angular.isDefined($scope.question.answers[index].history['resiexchange_answer_flag'])) {
                        $scope.question.answers[index].history['resiexchange_answer_flag'] = false;
                    }
                    // update current state to new values (toggle flag)
                    if($scope.question.answers[index].history['resiexchange_answer_flag'] === true) {
                        $scope.question.answers[index].history['resiexchange_answer_flag'] = false;
                    }
                    else {
                        $scope.question.answers[index].history['resiexchange_answer_flag'] = true;
                    }
                }
            };

            // set previous state and begin transaction
            $scope.begin(commit, { answers: $scope.question.answers });
            
            // remember selector for popover location 
            var selector = feedbackService.selector($event.target);           
            
            actionService.perform({
                // valid name of the action to perform server-side
                action: 'resiexchange_answer_flag',
                // string representing the data to submit to action handler (i.e.: serialized value of a form)
                data: {
                        answer_id: $scope.question.answers[index].id
                },
                // scope in wich callback function will apply 
                scope: $scope,
                // callback function to run after action completion (to handle error cases, ...)
                callback: function($scope, data) {
                    // we need to do it this way because current controller might be destroyed in the meantime
                    // toggle related entries in current history
                    if(data.result < 0) {
                        $scope.rollback();
                        // result is an error code
                        var error_id = data.error_message_ids[0];                    
                        // todo : get error_id translation
                        var msg = error_id;
                        feedbackService.popover(selector, msg);                    
                    }                
                    else {
                        commit($scope);
                        //$scope.question.answers[index].history['resiexchange_answer_flag'] = data.result;
                    }
                }        
            });
        };
        
        $scope.answerComment = function($event, index) {
            
            // remember selector for popover location 
            var selector = feedbackService.selector($event.target);
            
            actionService.perform({
                // valid name of the action to perform server-side
                action: 'resiexchange_answer_comment',
                // string representing the data to submit to action handler (i.e.: serialized value of a form)
                data: {
                    answer_id: $scope.question.answers[index].id,
                    content: $scope.question.answers[index].newCommentContent
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
                        var answer_id = $scope.question.answers[index].id;
                        var comment_id = data.result.id;
                        // add new comment to the list
                        $scope.question.answers[index].comments.push(data.result);
                        $scope.question.answers[index].newCommentShow = false;
                        $scope.question.answers[index].newCommentContent = '';
                        // wait for next digest cycle
                        $timeout(function() {
                            // scroll to newly created comment
                            feedbackService.popover('#comment-'+answer_id+'-'+comment_id, '');
                        });
                    }
                }        
            });
        };    
            
        $scope.answerCommentVoteUp = function ($event, answer_index, index) {
            
            // define transaction
            var commit = function ($scope) {
                // prevent action if it has already been committed
                if(!angular.isDefined($scope.committed) || !$scope.committed) {                    
                    // make sure impacted properties are set
                    if(!angular.isDefined($scope.question.answers[answer_index].comments[index].history['resiexchange_answercomment_voteup'])) {
                        $scope.question.answers[answer_index].comments[index].history['resiexchange_answercomment_voteup'] = false;
                    }                    
                    // update current state to new values 
                    if($scope.question.answers[answer_index].comments[index].history['resiexchange_answercomment_voteup'] === true) {
                        // undo voteup
                        $scope.question.answers[answer_index].comments[index].history['resiexchange_answercomment_voteup'] = false;
                        $scope.question.answers[answer_index].comments[index].score--;
                    }
                    else {
                        // voteup
                        $scope.question.answers[answer_index].comments[index].history['resiexchange_answercomment_voteup'] = true;
                        $scope.question.answers[answer_index].comments[index].score++;
                    }
                }
            };
            
            // set previous state and begin transaction
            $scope.begin(commit, { answers: $scope.question.answers });
            
            // remember selector for popover location 
            var selector = feedbackService.selector($event.target);
            
            actionService.perform({
                // valid name of the action to perform server-side
                action: 'resiexchange_answercomment_voteup',
                // string representing the data to submit to action handler (i.e.: serialized value of a form)
                data: {
                        comment_id: $scope.question.answers[answer_index].comments[index].id
                },
                // scope in wich callback function will apply 
                scope: $scope,
                // callback function to run after action completion (to handle error cases, ...)
                callback: function($scope, data) {
                    // we need to do it this way because current controller might be destroyed in the meantime
                    // toggle related entries in current history
                    if(data.result < 0) {
                        // rollback transaction
                        $scope.rollback();
                        
                        // result is an error code
                        var error_id = data.error_message_ids[0];                    
                        // todo : get error_id translation
                        var msg = error_id;
                        feedbackService.popover(selector, msg);                    
                    }                
                    else {
                        // commit if it hasn't been done already
                        commit($scope);
                        /*
                        $scope.question.answers[answer_index].comments[index].history['resiexchange_answercomment_voteup'] = data.result;
                        if(data.result === true) {
                            $scope.question.answers[answer_index].comments[index].score++;
                        }
                        else {
                            $scope.question.answers[answer_index].comments[index].score--;
                        }
                        */
                    }
                }        
            });
        };

        $scope.answerCommentFlag = function ($event, answer_index, index) {

            // define transaction
            var commit = function ($scope) {
                // prevent action if it has already been committed
                if(!angular.isDefined($scope.committed) || !$scope.committed) {                    
                    // make sure impacted properties are set
                    if(!angular.isDefined($scope.question.answers[answer_index].comments[index].history['resiexchange_answercomment_flag'])) {
                        $scope.question.answers[answer_index].comments[index].history['resiexchange_answercomment_flag'] = false;
                    }                    
                    // update current state to new values (toggle flag)
                    if($scope.question.answers[answer_index].comments[index].history['resiexchange_answercomment_flag'] === true) {
                        $scope.question.answers[answer_index].comments[index].history['resiexchange_answercomment_flag'] = false;
                    }
                    else {
                        $scope.question.answers[answer_index].comments[index].history['resiexchange_answercomment_flag'] = true;
                    }
                }
            };
            
            // set previous state and begin transaction
            $scope.begin(commit, { answers: $scope.question.answers });
            
            // remember selector for popover location             
            var selector = feedbackService.selector($event.target);
            
            actionService.perform({
                // valid name of the action to perform server-side
                action: 'resiexchange_answercomment_flag',
                // string representing the data to submit to action handler (i.e.: serialized value of a form)
                data: {
                        comment_id: $scope.question.answers[answer_index].comments[index].id
                },
                // scope in wich callback function will apply 
                scope: $scope,
                // callback function to run after action completion (to handle error cases, ...)
                callback: function($scope, data) {
                    // we need to do it this way because current controller might be destroyed in the meantime
                    // toggle related entries in current history
                    if(data.result < 0) {
                        // rollback transaction
                        $scope.rollback();
                        
                        // result is an error code
                        var error_id = data.error_message_ids[0];                    
                        // todo : get error_id translation
                        var msg = error_id;
                        feedbackService.popover(selector, msg);                    
                    }                
                    else {
                        // commit if it hasn't been done already
                        commit($scope);
                        // $scope.question.answers[answer_index].comments[index].history['resiexchange_answercomment_flag'] = data.result;
                    }
                }        
            });
        };

        $scope.answerDelete = function ($event, index) {
            // remember selector for popover location             
            var selector = feedbackService.selector($event.target);            
            
            ctrl.open('MODAL_ANSWER_DELETE_TITLE', 'MODAL_ANSWER_DELETE_HEADER', $scope.question.answers[index].content_excerpt)
            .then(
                function () {
                    actionService.perform({
                        // valid name of the action to perform server-side
                        action: 'resiexchange_answer_delete',
                        // string representing the data to submit to action handler (i.e.: serialized value of a form)
                        data: {answer_id: $scope.question.answers[index].id},
                        // scope in wich callback function will apply 
                        scope: $scope,
                        // callback function to run after action completion (to handle error cases, ...)
                        callback: function($scope, data) {
                            // we need to do it this way because current controller might be destroyed in the meantime
                            // (if route is changed to signin form)
                            if(data.result === true) {                  
                                $scope.question.answers.splice(index, 1);
                                // show user-answer block
                                $scope.question.history['resiexchange_question_answer'] = false;                    
                            }
                            else if(data.result === false) { 
                                // deletion toggle : we shouldn't reach this point with this controller
                            }
                            else {
                                // result is an error code
                                var error_id = data.error_message_ids[0];                    
                                // todo : get error_id translation
                                var msg = error_id;
                                feedbackService.popover(selector, msg);
                            }
                        }        
                    });
                }
            );     
        };
        
    }
]);
angular.module('resiexchange')
/**
* Display given question for edition
*
*/
.controller('questionEditController', [
    'question',
    '$scope', 
    '$window', 
    '$location', 
    '$sce', 
    'feedbackService', 
    'actionService', 
    'textAngularManager',
    '$http',
    '$httpParamSerializerJQLike',
    function(question, $scope, $window, $location, $sce, feedbackService, actionService, textAngularManager, $http, $httpParamSerializerJQLike) {
        console.log('questionEdit controller');
        
        var ctrl = this;   

        // @view 
       
        $scope.addItem = function(query) {
            return {
                id: null, 
                title: query, 
                path: query, 
                parent_id: 0, 
                parent_path: ''
            };
        };
        
        $scope.loadMatches = function(query) {
            if(query.length < 2) return [];
            
            return $http.get('index.php?get=resiway_category_list&order=title&'+$httpParamSerializerJQLike({domain: ['title', 'ilike', '%'+query+'%']}))
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
        
        // @model
        // content is inside a textarea and do not need sanitize check
        question.content = $sce.valueOf(question.content);
        
        $scope.question = angular.merge({
                            id: 0,
                            title: '',
                            content: '',
                            tags_ids: [{}]
                          }, 
                          question);
                          

        /**
        * tags_ids is a many2many field, so as initial setting we mark all ids to be removed
        */
        // save initial tags_ids
        $scope.initial_tags_ids = [];
        angular.forEach($scope.question.tags, function(tag, index) {
            $scope.initial_tags_ids.push('-'+tag.id);
        });
        
        // @events
        $scope.$watch('question.tags', function() {
            // reset selection
            $scope.question.tags_ids = angular.copy($scope.initial_tags_ids);
            angular.forEach($scope.question.tags, function(tag, index) {
                if(tag.id == null) {
                    $scope.question.tags_ids.push(tag.title);
                }
                else $scope.question.tags_ids.push('+'+tag.id);
            });
        });

        // @methods
        $scope.questionPost = function($event) {
            var selector = feedbackService.selector(angular.element($event.target));                   
            actionService.perform({
                // valid name of the action to perform server-side
                action: 'resiexchange_question_edit',
                // string representing the data to submit to action handler (i.e.: serialized value of a form)
                data: {
                    question_id: $scope.question.id,
                    title: $scope.question.title,
                    content: $scope.question.content,
                    tags_ids: $scope.question.tags_ids
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
                        // in case a field is missing, adapt the generic 'missing_*' message
                        if(msg.substr(0, 8) == 'missing_') {
                            msg = 'question_'+msg;
                        }
                        feedbackService.popover(selector, msg);
                    }
                    else {
                        var question_id = data.result.id;
                        $location.path('/question/'+question_id);
                    }
                }        
            });
        };  
           
    }
]);
angular.module('resiexchange')

.controller('questionsController', [
    'questions', 
    '$scope',
    '$rootScope',
    '$route',
    '$http',
    '$httpParamSerializerJQLike',
    '$window',
    function(questions, $scope, $rootScope, $route, $http, $httpParamSerializerJQLike, $window) {
        console.log('questions controller');

        var ctrl = this;

        // @data model
        angular.merge(ctrl, {
            questions: {
                items: questions,
                total: $rootScope.search.total,
                currentPage: 1,
                previousPage: -1,                
                limit: $rootScope.search.criteria.limit
            }
        });

        ctrl.load = function() {
            if(ctrl.questions.currentPage != ctrl.questions.previousPage) {
                ctrl.questions.previousPage = ctrl.questions.currentPage;
                // reset objects list (triggers loader display)
                ctrl.questions.items = -1;
                $rootScope.search.criteria.start = (ctrl.questions.currentPage-1)*ctrl.questions.limit;
                
                $http.get('index.php?get=resiexchange_question_list&'+$httpParamSerializerJQLike($rootScope.search.criteria))
                .then(
                    function successCallback(response) {
                        var data = response.data;
                        if(typeof data.result != 'object') {
                            ctrl.questions.items = [];
                        }
                        ctrl.questions.items = data.result;
                        $window.scrollTo(0, 0);
                    },
                    function errorCallback(response) {
                        // something went wrong server-side
                        return [];
                    }
                );
            }
        };            

        // @async loads
        ctrl.categories = [];
        
        angular.forEach($rootScope.search.criteria.domain, function(clause, i) {
            if(clause[0] == 'categories_ids') {
                $scope.related_categories = [];
                if(typeof clause[2] != 'object') {
                    clause[2] = [clause[2]];
                }
                ctrl.categories = clause[2];
            }
        });
        
        /*
        * async load and inject $scope.categories and $scope.related_categories
        */
        if(ctrl.categories.length > 0) {
            $http.get('index.php?get=resiway_category_list&'+$httpParamSerializerJQLike({domain: ['id', 'in', ctrl.categories]}))
            .then(
                function successCallback(response) {
                    var data = response.data;
                    if(typeof data.result == 'object') {
                        $scope.categories = data.result;
                    }
                }
            );
            angular.forEach(ctrl.categories, function(category_id, j) {
                $http.get('index.php?get=resiway_category_related&category_id='+category_id)
                .then(
                    function successCallback(response) {
                        var data = response.data;
                        if(typeof data.result == 'object') {
                            $scope.related_categories = data.result;
                        }
                    }
                );
                
            });
        }
        
        /*
        * async load and inject $scope.categories and $scope.related_categories
        */
        $http.get('index.php?get=resiway_category_list&limit=15&order=count_questions&sort=desc')
        .then(
            function successCallback(response) {
                var data = response.data;
                if(typeof data.result == 'object') {
                    $scope.featured_categories = data.result;
                }
            }
        );
        
    }
]);
angular.module('resiexchange')

/**
* Top Bar Controller
* 
* 
*/
.controller('topBarCtrl', [
    '$scope',
    '$rootScope', 
    '$document',
    '$http',
    'actionService',
    'authenticationService',
    function($scope, $rootScope, $document, $http, action, authentication) {
        console.log('topbar controller');
        
        var ctrl = this;
        
        // @model
        ctrl.platformDropdown = false;
        ctrl.userDropdown = false;
        ctrl.notifyDropdown = false;
        ctrl.helpDropdown = false;
        
        function hideAll() {
            ctrl.platformDropdown = false;
            ctrl.userDropdown = false;
            ctrl.notifyDropdown = false;            
            ctrl.helpDropdown = false;            
        }

        angular.element(document.querySelectorAll('#topBar a')).on('click', function() {
            hideAll();
        });
        
        function documentClickBind(event) {
            if(event) {
                var $targetScope = angular.element(event.target).scope();
                while($targetScope) {               
                    if($scope.$id == $targetScope.$id) {
                        return false;
                    }
                    $targetScope = $targetScope.$parent;
                }            
            }
            $scope.$apply(function() {
                hideAll();
                $document.off('click', documentClickBind);
            });            
        }
        
        // @events
            
        $scope.togglePlatformDropdown = function() {
            var flag = ctrl.platformDropdown;
            hideAll();     
            if(!flag) $document.on('click', documentClickBind);   
            else $document.off('click', documentClickBind);
            ctrl.platformDropdown = !flag;                        
        };
        
        $scope.toggleUserDropdown = function() {
            var flag = ctrl.userDropdown;
            hideAll();
            if(!flag) $document.on('click', documentClickBind);   
            else $document.off('click', documentClickBind);
            ctrl.userDropdown = !flag;
        };

        $scope.toggleNotifyDropdown = function() {
            var flag = ctrl.notifyDropdown;            
            hideAll();
            if(!flag) $document.on('click', documentClickBind);   
            else $document.off('click', documentClickBind);
            ctrl.notifyDropdown = !flag;
        };

        $scope.toggleHelpDropdown = function() {
            var flag = ctrl.helpDropdown;            
            hideAll();
            if(!flag) $document.on('click', documentClickBind);   
            else $document.off('click', documentClickBind);
            ctrl.helpDropdown = !flag;
        };
        
        ctrl.signOut = function(){          
            action.perform({
                action: 'resiway_user_signout',
                next_path: '/',
                callback: function($scope, data) {
                    authentication.clearCredentials();
                }
            });
        };
        
        ctrl.notificationsDismissAll = function() {
            $rootScope.user.notifications = [];            
            $http.get('index.php?do=resiway_notification_dismiss-all');
        };
                
    }
]);
angular.module('resiexchange')

.controller('userConfirmController', [
    '$scope',
    '$rootScope',
    '$routeParams',
    '$http',
    'authenticationService',
    function($scope, $rootScope, $routeParams, $http, authenticationService) {
        console.log('userConfirm controller');

        var ctrl = this;

        ctrl.code = $routeParams.code;
        ctrl.verified = false;
        ctrl.password_updated = false;        
        ctrl.closeAlerts = function() {
            $scope.alerts = [];
        };
        
        $scope.password = '';
        $scope.confirm = '';    
        $scope.alerts = [];

         // @init
        $http.get('index.php?do=resiway_user_confirm&code='+ctrl.code)
        .then(
        function successCallback(response) {
            var data = response.data;
            if(typeof response.data.result != 'undefined'
            && response.data.result === true) {
                ctrl.verified = data.result;
                if(typeof data.notifications != 'undefined' && data.notifications.length > 0) {                
                    $rootScope.user.notifications = $rootScope.user.notifications.concat(data.notifications);
                }
                // we should now be able to authenticate (session is initiated)
                authenticationService.authenticate();                
            }
        },
        function errorCallback() {
            // something went wrong server-side
        });
        
        ctrl.passwordReset = function() {
            $scope.alerts = [];
            if($scope.password.length == 0 || $scope.password != $scope.confirm) {
                if($scope.password.length == 0) {
                    $scope.alerts.push({ type: 'warning', msg: 'Please, provide a new password.' });                
                }
                else if($scope.confirm.length == 0) {
                    $scope.alerts.push({ type: 'warning', msg: 'Please, re-type your new password.' });                
                }
                else if($scope.password != $scope.confirm) {
                    $scope.alerts.push({ type: 'warning', msg: 'Confirmation does not match the specified password.' });                
                }                
            }
            else {
                $http.get('index.php?do=resiway_user_passwordreset&password='+md5($scope.password)+'&confirm='+md5($scope.confirm))
                .then(
                function successCallback(response) {
                    var data = response.data;
                    if(typeof response.data.result != 'undefined'
                    && response.data.result === true) {
                        ctrl.password_updated = data.result;
                    }
                },
                function errorCallback() {
                    // something went wrong server-side
                });                
            }
        };
        
    }
]);
angular.module('resiexchange')
/**
* Display given user public profile for edition
*
*/
.controller('userEditController', [
    'user',
    '$scope',
    '$window',
    '$filter',
    '$http',
    '$translate',
    'feedbackService',
    'actionService',
    function(user, $scope, $window, $filter, $http, $translate, feedback, action) {
    console.log('userEdit controller');    
    
    var ctrl = this;

    ctrl.user = user;
    
    if(Object.keys(user).length == 0) {
        console.log('empty object');
        return;
    }
    ctrl.publicity_mode = {id: 1, text: 'Fullname'};
    
    ctrl.modes = [ 
        {id: 1, text: 'Fullname'}, 
        {id: 2, text: 'Firstname + Lastname inital'}, 
        {id: 3, text: 'Firstname only'}
    ];
    $translate(['USER_EDIT_PUBLICITY_MODE_FULLNAME','USER_EDIT_PUBLICITY_MODE_FIRSTNAME_L','USER_EDIT_PUBLICITY_MODE_FIRSTNAME'])
    .then(function (translations) {
        ctrl.modes[0].text = translations['USER_EDIT_PUBLICITY_MODE_FULLNAME'];
        ctrl.modes[1].text = translations['USER_EDIT_PUBLICITY_MODE_FIRSTNAME_L'];
        ctrl.modes[2].text = translations['USER_EDIT_PUBLICITY_MODE_FIRSTNAME'];        
    })
    .then(function() {
        angular.forEach(ctrl.modes, function(mode) {
            if(mode.id == ctrl.user.publicity_mode) {
                ctrl.publicity_mode = {id: mode.id, text: mode.text};                
            }
        });        
    });
    
    ctrl.avatars = {
        libravatar: 'https://seccdn.libravatar.org/avatar/'+md5(ctrl.user.login)+'?s=@size',
        gravatar: 'https://www.gravatar.com/avatar/'+md5(ctrl.user.login)+'?s=@size',
        identicon: 'https://www.gravatar.com/avatar/'+md5(ctrl.user.firstname+ctrl.user.id)+'?d=identicon&s=@size',
        google: ''
    };
        
    // @init
    // retrieve GMail avatar, if any
    $http.get('https://picasaweb.google.com/data/entry/api/user/'+ctrl.user.login+'?alt=json')
    .then(
        function successCallback(response) {
            var url = response.data['entry']['gphoto$thumbnail']['$t'];
            ctrl.avatars.google = url.replace("/s64-c/", "/")+'?sz=@size';
        },
        function errorCallback(response) {

        }
    );     

    
    $scope.$watchGroup([
            function(){return ctrl.publicity_mode;},
            function(){return ctrl.user.firstname;},
            function(){return ctrl.user.lastname;}
        ], function() {
        ctrl.user.publicity_mode = ctrl.publicity_mode.id;
        switch(ctrl.user.publicity_mode) {
        case 1:
            ctrl.user.display_name = ctrl.user.firstname+' '+ctrl.user.lastname;
            break;
        case 2:
            var lastname = '';
            if(ctrl.user.lastname.length) {
                lastname = ctrl.user.lastname.substr(0, 1)+'.';
            }
            ctrl.user.display_name = ctrl.user.firstname+' '+lastname;
            break;
        case 3:
            ctrl.user.display_name = ctrl.user.firstname;
            break;
        }                
    });  
    
    ctrl.userPost = function($event) {
        var selector = feedback.selector(angular.element($event.target));                   
        action.perform({
            // valid name of the action to perform server-side
            action: 'resiway_user_edit',
            // string representing the data to submit to action handler (i.e.: serialized value of a form)
            data: {
                id: ctrl.user.id,
                firstname: ctrl.user.firstname,
                lastname: ctrl.user.lastname,
                publicity_mode: ctrl.user.publicity_mode,
                language: ctrl.user.language,
                country: ctrl.user.country,
                location: ctrl.user.location,
                about: ctrl.user.about,
                avatar_url: ctrl.user.avatar_url,
                notify_reputation_update: ctrl.user.notify_reputation_update,
                notify_badge_awarded: ctrl.user.notify_badge_awarded,
                notify_question_comment: ctrl.user.notify_question_comment,
                notify_answer_comment: ctrl.user.notify_answer_comment,
                notify_question_answer: ctrl.user.notify_question_answer
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
                    feedback.popover(selector, msg);
                }
                else {
                    // scroll to top
                    $window.scrollTo(0, 0);
                    $scope.showMessage = true;
                }
            }        
        });
    };  
}]);
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
angular.module('resiexchange')

/**
* 
* 
* 
*/
.controller('userPasswordController', [
    '$scope',
    '$routeParams',
    '$http',
    'authenticationService',
    function($scope, $routeParams, $http, authenticationService) {
        console.log('userPassword controller');
        
        var ctrl = this;

        // @model             
        $scope.password = '';
        $scope.confirm = '';    
        $scope.alerts = [];
        // alerts format : { type: 'danger|warning|success', msg: 'Alert message.' }

        ctrl.code = $routeParams.code;        
        ctrl.password_updated = false;   
        ctrl.closeAlerts = function() {
            $scope.alerts = [];
        };

        // @init        
        if(typeof ctrl.code != 'undefined') {
            var decoded = String(ctrl.code).base64_decode();
            if(decoded.indexOf(';') > 0) {
                var params = decoded.split(';');
                $http.get('index.php?do=resiway_user_signin&login='+params[0]+'&password='+params[1])
                .then(
                function successCallback(response) {
                    var data = response.data;
                    if(typeof response.data.result != 'undefined'
                    && response.data.result > 0) {
                        ctrl.verified = data.result;
                        // we should now be able to authenticate (session is initiated)
                        authenticationService.authenticate();
                    }
                },
                function errorCallback() {
                    // something went wrong server-side
                });
            }
        }
        
        ctrl.passwordReset = function() {
            $scope.alerts = [];            
            if($scope.password.length == 0 || $scope.password != $scope.confirm) {
                if($scope.password.length == 0) {
                    $scope.alerts.push({ type: 'warning', msg: 'Please, provide a new password.' });                
                }
                else if($scope.confirm.length == 0) {
                    $scope.alerts.push({ type: 'warning', msg: 'Please, re-type your new password.' });                
                }
                else if($scope.password != $scope.confirm) {
                    $scope.alerts.push({ type: 'warning', msg: 'Confirmation does not match the specified password.' });                
                }                
            }
            else {
                $http.get('index.php?do=resiway_user_passwordreset&password='+md5($scope.password)+'&confirm='+md5($scope.confirm))
                .then(
                function successCallback(response) {
                    var data = response.data;
                    if(typeof response.data.result != 'undefined'
                    && response.data.result === true) {
                        ctrl.password_updated = data.result;
                    }
                },
                function errorCallback() {
                    // something went wrong server-side
                });                
            }
        };

    }
]);
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
angular.module('resiexchange')

/**
* 
* Once successfully identified, this controller will redirect to previously stored location, if any
* this controller displays a form for collecting user credentials
*/
.controller('userSignController', [
    '$scope', 
    '$rootScope', 
    '$location', 
    '$routeParams', 
    '$http',
    'authenticationService',
    function($scope, $rootScope, $location, $routeParams, $http, authenticationService) {
        console.log('userSign controller');
        
        var ctrl = this;
        
        // set default mode to blank
        ctrl.mode = ''; 
        
        // asign mode from URL if it matches one of the allowed modes
        switch($routeParams.mode) {
            case 'recover':
            case 'in': 
            case 'up': 
            ctrl.mode = $routeParams.mode;
        }


        // @model             
        $scope.remember = true;
        $scope.username = '';
        $scope.password = '';
        $scope.firstname = '';
        $scope.email = '';    
        $scope.signInAlerts = [];
        $scope.signUpAlerts = [];    
        $scope.recoverAlerts = [];
        // alerts format : { type: 'danger|warning|success', msg: 'Alert message.' }
        
        ctrl.recovery_sent = false;
        
        ctrl.closeSignInAlerts = function() {
            $scope.signInAlerts = [];
        };
        
        ctrl.closeSignInAlert = function(index) {
            $scope.signInAlerts.splice(index, 1);
        };

        ctrl.closeSignUpAlerts = function() {
            $scope.signUpAlerts = [];
        };
        
        ctrl.closeSignUpAlert = function(index) {
            $scope.signUpAlerts.splice(index, 1);
        };

        ctrl.closeRecoverAlerts = function() {
            $scope.recoverAlerts = [];
        };
        
        ctrl.closeRecoverAlert = function(index) {
            $scope.recoverAlerts.splice(index, 1);
        };
            
        ctrl.signIn = function () {
            if($scope.username.length == 0 || $scope.password.length == 0) {
                if($scope.username.length == 0) {
                    $scope.signInAlerts.push({ type: 'warning', msg: 'Please, provide your email as identifier.' });                
                }
                else if($scope.password.length == 0) {
                    $scope.signInAlerts.push({ type: 'warning', msg: 'Please, provide your password.' });                
                }
            }
            else {
                ctrl.running = true;                
                // form is complete
                ctrl.closeSignInAlerts();                
                authenticationService.setCredentials($scope.username, md5($scope.password), $scope.remember);
                // attempt to log the user in
                authenticationService.authenticate().then(
                function successHandler(data) {
                    ctrl.running = false;
                    // if some action is pending, return to URL where it occured
                    if($rootScope.pendingAction
                    && typeof $rootScope.pendingAction.next_path != 'undefined') {
                       $location.path($rootScope.pendingAction.next_path);
                    }
                    else {
                        $location.path($rootScope.previousPath);
                    }
                },
                function errorHandler() {
                    ctrl.running = false;
                    authenticationService.clearCredentials();
                    $scope.signInAlerts = [{ type: 'danger', msg: 'Email or password mismatch.' }];
                });        
            }
        };
        
        ctrl.signUp = function() {
            if($scope.username.length == 0 || $scope.firstname.length == 0) {
                if($scope.firstname.length == 0) {
                    $scope.signUpAlerts.push({ type: 'warning', msg: 'Please, indicate your firstname.' });                
                }                
                else if($scope.username.length == 0) {
                    $scope.signUpAlerts.push({ type: 'warning', msg: 'Please, provide your email as username.' });                
                }
            }
            else {
                ctrl.running = true;
                ctrl.closeSignUpAlerts();                
                authenticationService.register($scope.username, $scope.firstname).then(
                function successHandler(data) {
                    ctrl.running = false;
                    authenticationService.authenticate().then(
                    function successHandler(data) {
                        // actively request emails
                        $http.get('index.php?do=resiway_user_pull');
                        // if some action is pending, return to URL where it occured
                        if($rootScope.pendingAction
                        && typeof $rootScope.pendingAction.next_path != 'undefined') {
                           $location.path($rootScope.pendingAction.next_path);
                        }
                        else {
                            $location.path($rootScope.previousPath);
                        }
                    },
                    function errorHandler(data) {
                        authenticationService.clearCredentials();
                        $scope.signUpAlerts = [{ type: 'danger', msg: 'Sorry, an unexpected error occured.' }];
                    });  
                },
                function errorHandler(data) {
                    ctrl.running = false;
                    var error_id = data.error_message_ids[0];     
                    // server fault, email already registered, ...
                    $scope.signUpAlerts = [{ type: 'danger', msg: error_id }];
                });             

            }
        };

        ctrl.recover = function () {
            if($scope.email.length == 0) {
                $scope.recoverAlerts.push({ type: 'warning', msg: 'Please, provide your email.' });
            }
            else {
                ctrl.running = true;
                ctrl.closeRecoverAlerts();
                $http.get('index.php?do=resiway_user_passwordrecover&email='+$scope.email)
                .then(
                function successCallback(response) {
                    ctrl.running = false;
                    var data = response.data;
                    if(typeof response.data.result != 'undefined'
                    && response.data.result === true) {
                        ctrl.recovery_sent = data.result;
                    }
                },
                function errorCallback() {
                    ctrl.running = false;
                    var error_id = data.error_message_ids[0];     
                    // server fault, user not verified, ...
                    $scope.recoverAlerts = [{ type: 'danger', msg: error_id }];
                });                  
            }
        };    
    }
]);