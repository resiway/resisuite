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