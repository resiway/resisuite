<?php
defined('__QN_LIB') or die(__FILE__.' cannot be executed directly.');
require_once('../resi.api.php');
$rev = ResiAPI::currentRevision(); 
$token = md5($rev.rand(1, 100));
?>
<!DOCTYPE html>
<html lang="fr" ng-app="resiexchange" id="top" ng-controller="rootController as rootCtrl">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
        <meta name="title" content="ResiWay - La plateforme pour la résilience">        
        <meta name="description" content="">    

        <title>ResiWay</title>

        <link type="image/png" sizes="180x180" href="/packages/resiway/apps/assets/icons/apple-icon-180x180.png" rel="apple-touch-icon">
        <link type="image/png" sizes="192x192" href="/packages/resiway/apps/assets/icons/android-icon-192x192.png" rel="icon">
        <link type="image/png" sizes="32x32"   href="/packages/resiway/apps/assets/icons/favicon-32x32.png" rel="icon">
        <link type="image/png" sizes="96x96"   href="/packages/resiway/apps/assets/icons/favicon-96x96.png" rel="icon">
        <link type="image/png" sizes="16x16"   href="/packages/resiway/apps/assets/icons/favicon-16x16.png" rel="icon">
        
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
        
        <script src="packages/resiexchange/apps/resiexchange.min.js?<?php echo $token; ?>"></script>        
        <link rel="stylesheet" type="text/css" href="packages/resiexchange/apps/assets/css/resiexchange.min.css?<?php echo $token; ?>" />

        <script>
        var global_config = {
            application: 'resiway',
            locale: 'fr',
            channel: '1'
        };
        </script>
        <script>
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
        (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
        m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
        ga('create', 'UA-93932085-1', 'auto');
        ga('send', 'pageview');
        </script>        
    </head>


    <body class="ng-cloak">
        <!-- templates in rootScope -->
        <?php
        foreach (glob("packages/resiway/apps/views/*.html") as $filename) {
            echo '<script type="text/ng-template" id="'.basename($filename).'">'."\n";
            echo file_get_contents($filename)."\n";
            echo "</script>\n";
        }
        ?>
        
        <!-- topbar -->
        <?php echo file_get_contents("packages/resiexchange/apps/views/parts/topbar.html"); ?>

        <div id="body">   
            <div class="modal-wrapper"></div>
            <div class="container">
                <!-- menu -->
                <?php //echo file_get_contents("packages/resiway/apps/views/parts/menu.html"); ?>
                <!-- loader -->                
                <div ng-show="viewContentLoading" class="loader"><i class="fa fa-spin fa-spinner" aria-hidden="true"></i></div>
                <div ng-view ng-hide="viewContentLoading"></div>
            </div>
        </div>

        <div id="footer">
            <div class="grid wrapper">
                <div class="container col-1-1">
                    <!-- footer -->
                    <?php echo file_get_contents("packages/resiexchange/apps/views/parts/footer.html"); ?>                    
                    <span class="small">rev <?php echo $rev; ?></span>
                </div>
            </div>
        </div>    
    </body>
    
</html>