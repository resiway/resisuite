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