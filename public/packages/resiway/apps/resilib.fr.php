<!DOCTYPE html>
<html lang="fr" ng-app="resiexchange" id="top" ng-controller="rootController as rootCtrl">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
        <meta name="title" content="ResiExchange - Des réponses pour la résilience">
        <meta name="description" content="ResiExchange est une plateforme collaborative open source d'échange d'informations sur les thèmes de l'autonomie, la transition, la permaculture et la résilience.">

        <title>ResiLib</title>


        <script src="packages/resiexchange/apps/assets/js/moment.min.js"></script>
        <script src="packages/resiexchange/apps/assets/js/md5.js"></script>
        
        <script src="packages/resiexchange/apps/assets/js/angular.min.js"></script>
        <script src="packages/resiexchange/apps/assets/js/angular-animate.min.js"></script>
        <script src="packages/resiexchange/apps/assets/js/angular-touch.min.js"></script>
        <script src="packages/resiexchange/apps/assets/js/angular-sanitize.js"></script>
        <script src="packages/resiexchange/apps/assets/js/angular-cookies.js"></script>
        <script src="packages/resiexchange/apps/assets/js/angular-route.min.js"></script>    
        <script src="packages/resiexchange/apps/assets/js/angular-translate.min.js"></script>
        <script src="packages/resiexchange/apps/assets/js/angular-moment.min.js"></script>        
        
        <script src="packages/resiexchange/apps/assets/js/ui-bootstrap-tpls-2.2.0.min.js"></script>        
        <script src='packages/resiexchange/apps/assets/js/textAngular-rangy.min.js'></script>
        <script src='packages/resiexchange/apps/assets/js/textAngular-sanitize.min.js'></script>
        <script src='packages/resiexchange/apps/assets/js/textAngular.min.js'></script>   
        <script src='packages/resiexchange/apps/assets/js/ngToast.min.js'></script>        
        <script src='packages/resiexchange/apps/assets/js/select-tpls.min.js'></script>

        <script src="packages/resiexchange/apps/i18n/locale-fr.js"></script>
        <script src="packages/resiexchange/apps/i18n/moment-locale/fr.js"></script>
             
        <link rel="stylesheet" type="text/css" href="packages/resiexchange/apps/assets/css/bootstrap.min.css" />
        <link rel="stylesheet" type="text/css" href="packages/resiexchange/apps/assets/css/font-awesome.min.css" />
        <link rel="stylesheet" type="text/css" href="packages/resiexchange/apps/assets/css/ngToast.min.css" />
        <link rel="stylesheet" type="text/css" href="packages/resiexchange/apps/assets/css/ngToast-animations.min.css" />
                
        <script src="packages/resiexchange/apps/resiexchange.min.js"></script>        
        <link rel="stylesheet" type="text/css" href="packages/resiexchange/apps/assets/css/resiexchange.min.css" />

        <script>
        var global_config = {
            application: 'resilib',
            locale: 'fr',
            channel: '1'
        };
        </script>
        
        <style>
        #main_iframe {
            position: fixed;
            border: 0;
            top: 33px;;
            left: 0;
            width: 100%;
            height: calc(100% - 32px);  
            z-index: 1;
        }        
        </style>
    </head>
        
    <body class="ng-cloak">
        <!-- topbar -->
        <?php echo file_get_contents("packages/resiexchange/apps/views/parts/topbar.html"); ?>

        <script type="text/ng-template" id="home.html"></script>
        
        <iframe id="main_iframe" src="/resilib"></iframe>

    </body>
</html>