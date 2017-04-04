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