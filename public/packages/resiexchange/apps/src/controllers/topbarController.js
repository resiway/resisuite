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