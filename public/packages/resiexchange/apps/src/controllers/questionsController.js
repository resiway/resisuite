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