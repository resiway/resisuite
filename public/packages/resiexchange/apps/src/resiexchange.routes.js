angular.module('resiexchange')

.config([
    '$routeProvider', 
    '$routeParamsProvider', 
    '$httpProvider',
    function($routeProvider, $routeParamsProvider, $httpProvider) {
        
        // var templatePath = 'packages/resiexchange/apps/views/';
        var templatePath = '';

        /**
        * Routes definition
        * This call associates handled URL with their related views and controllers
        * 
        * As a convention, a 'ctrl' member is always defined inside a controller as itself
        * so it can be manipulated the same way in view and in controller
        */
        $routeProvider
        /**
        * Help related routes
        */
        // display all categories with first 5 topics in each
        .when('/help/categories', {
            templateUrl : templatePath+'helpCategories.html',
            controller  : 'helpCategoriesController as ctrl',
            resolve     : {
                categories: ['routeHelpCategoriesProvider', function (provider) {
                    return provider.load();
                }]
            }
        })
        .when('/help/category/edit/:id', {
            templateUrl : templatePath+'helpCategoryEdit.html',
            controller  : 'helpCategoryEditController as ctrl',
            resolve     : {
                // request object data
                category: ['routeHelpCategoryProvider', function (provider) {
                    return provider.load();
                }]
            }        
        })        
        // display a single category with all topics
        .when('/help/category/:id/:title?', {
            templateUrl : templatePath+'helpCategory.html',
            controller  : 'helpCategoryController as ctrl',
            resolve     : {
                // request object data
                category: ['routeHelpCategoryProvider', function (provider) {
                    return provider.load();
                }]
            }        
        })
        .when('/help/topic/edit/:id', {
            templateUrl : templatePath+'helpTopicEdit.html',
            controller  : 'helpTopicEditController as ctrl',
            resolve     : {
                // request object data
                topic: ['routeHelpTopicProvider', function (provider) {
                    return provider.load();
                }],
                // list of categories is required as well for selecting parent category
                categories: ['routeHelpCategoriesProvider', function (provider) {
                    return provider.load();
                }]
            } 
        })           
        // display a topic with breadcrumb
        .when('/help/topic/:id/:title?', {
            templateUrl : templatePath+'helpTopic.html',
            controller  : 'helpTopicController as ctrl',
            resolve     : {
                topic: ['routeHelpTopicProvider', function (provider) {
                    return provider.load();
                }],
                // list of categories is required as well for displahing TOC
                categories: ['routeHelpCategoriesProvider', function (provider) {
                    return provider.load();
                }]                
            }
        })
        /**
        * Badges related routes
        */
        
        .when('/badges', {
            templateUrl : templatePath+'badges.html',
            controller  : 'badgesController as ctrl',
            resolve     : {
                categories: ['routeBadgeCategoriesProvider', function (provider) {
                    return provider.load();
                }]
            }
        })        
        /**
        * Category related routes
        */
        .when('/categories', {
            templateUrl : templatePath+'categories.html',
            controller  : 'categoriesController as ctrl',
            resolve     : {
                categories: ['routeCategoriesProvider', function (provider) {
                    return provider.load();
                }]
            }
        })
        .when('/category/edit/:id', {
            templateUrl : templatePath+'categoryEdit.html',
            controller  : 'categoryEditController as ctrl',
            resolve     : {
                // request object data
                category: ['routeCategoryProvider', function (provider) {
                    return provider.load();
                }]
            }        
        })
        .when('/category/:id', {
            templateUrl : templatePath+'category.html',
            controller  : 'categoryController as ctrl',
            resolve     : {
                category: ['routeCategoryProvider', function (provider) {
                    return provider.load();
                }]
            }            
        })      
        /**
        * Question related routes
        */
        .when('/questions', {
            templateUrl : templatePath+'questions.html',
            controller  : 'questionsController as ctrl',
            resolve     : {
                // list of categories is required as well for selecting parent category
                questions: ['routeQuestionsProvider', function (provider) {
                    return provider.load();
                }]
            }
        })
        .when('/question/edit/:id', {
            templateUrl : templatePath+'questionEdit.html',
            controller  : 'questionEditController as ctrl',
            resolve     : {
                question: ['routeQuestionProvider', function (provider) {
                    return provider.load();
                }]
            }        
        })    
        .when('/question/:id/:title?', {
            templateUrl : templatePath+'question.html',
            controller  : 'questionController as ctrl',
            resolve     : {
                question: ['routeQuestionProvider', function (provider) {
                    return provider.load();
                }]
            }
        })
        .when('/answer/edit/:id', {
            templateUrl : templatePath+'answerEdit.html',
            controller  : 'answerEditController as ctrl',
            resolve     : {
                answer: ['routeAnswerProvider', function (provider) {
                    return provider.load();
                }]
            }        
        })     
        .when('/answer/:id', {
            templateUrl : templatePath+'question.html',
            controller  : ['$location', 'routeAnswerProvider', function($location, routeAnswerProvider) {
                routeAnswerProvider.load().then(
                function(answer) {
                    $location.path('/question/'+answer.question_id);
                });                
            }]  
        })
        /**
        * User related routes
        */
        .when('/user/current/profile', {
            templateUrl : templatePath+'userProfile.html',
            controller  : ['$location', 'authenticationService', function($location, authenticationService) {
                authenticationService.userId().then(
                function(user_id) {
                    $location.path('/user/profile/'+user_id);
                });                
            }]  
        })          
        .when('/user/current/edit', {
            templateUrl : templatePath+'userEdit.html',
            controller  : ['$location', 'authenticationService', function($location, authenticationService) {
                authenticationService.userId().then(
                function(user_id) {
                    $location.path('/user/edit/'+user_id);
                });                
            }]  
        })         
        .when('/user/edit/:id', {
            templateUrl : templatePath+'userEdit.html',
            controller  : 'userEditController as ctrl',
            resolve     : {
                user: ['routeUserProvider', function (provider) {
                    return provider.load();
                }]
            }        
        })       
        .when('/user/profile/:id/:name?', {
            templateUrl : templatePath+'userProfile.html',
            controller  : 'userProfileController as ctrl',
            resolve     : {
                user:  ['routeUserProvider', function (provider) {
                    return provider.load();
                }]
            }             
        })
        .when('/user/password/:code?', {
            templateUrl : templatePath+'userPassword.html',
            controller  : 'userPasswordController as ctrl'          
        })        
        .when('/user/confirm/:code', {
            templateUrl : templatePath+'userConfirm.html',
            controller  : 'userConfirmController as ctrl'
        })            
        .when('/user/notifications/:id', {
            templateUrl : templatePath+'userNotifications.html',
            controller  : 'userNotificationsController as ctrl'
        })
        .when('/user/sign/:mode?', {
            templateUrl : templatePath+'userSign.html',
            controller  : 'userSignController as ctrl',
            reloadOnSearch: false
        })
        /**
        * Resiway routes            
        */        
        .when('/association/soutenir', {
            templateUrl : templatePath+'support.html',
            controller  : 'emptyController as ctrl'
        })
        .when('/association/participer', {
            templateUrl : templatePath+'participate.html',
            controller  : 'emptyController as ctrl'
        })
        .when('/association/mentions-legales', {
            templateUrl : templatePath+'legal.html',
            controller  : 'emptyController as ctrl'
        })        
        .when('/association', {
            templateUrl : templatePath+'organisation.html',
            controller  : 'emptyController as ctrl'
        })        
        /**
        * Default route
        */    
        .otherwise({
            templateUrl : templatePath+'home.html',
            controller  : 'homeController as ctrl'
        });
        
    }
]);