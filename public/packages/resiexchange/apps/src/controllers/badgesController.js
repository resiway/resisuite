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