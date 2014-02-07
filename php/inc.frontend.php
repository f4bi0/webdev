<!DOCTYPE>
<html>
    <head>
        <title><?php echo $title; ?></title>
        <meta charset="UTF-8" />
        <?php Frontend::js('$base_url/$base_name/js/frontend.js'); ?>
        <script>
            frontend.BASE_URL = '<?php echo Frontend::$config['base_url']; ?>';
        </script>
        <?php Frontend::js('$base_url/$base_name/js/jquery.js'); ?>
        <?php Frontend::js('$base_url/$base_name/js/jquery-ui.js'); ?>
        <?php Frontend::js('$base_url/$base_name/js/jquery_utils.js'); ?>
        <?php Frontend::js('$base_url/$base_name/js/knockout.js'); ?>
        <?php Frontend::js('$base_url/$base_name/js/ko_utils.js'); ?>
        <?php Frontend::js('$base_url/$base_name/js/underscore.js'); ?>
        <?php Frontend::js('$base_url/$base_name/js/offline.js'); ?>
        <?php Frontend::js('$base_url/$base_name/js/widgets.js'); ?>
        <?php Frontend::js('$base_url/$base_name/js/traca.js'); ?>
        <?php Frontend::css('$base_url/$base_name/css/offline.css'); ?>
        <?php Frontend::css('$base_url/$base_name/css/jquery-ui.css'); ?>
        <?php Frontend::css('$base_url/$base_name/css/custom.css'); ?>
        <?php Frontend::css('$base_url/$base_name/css/traca.css'); ?>
    </head>
    <body>
        <?php echo $content; ?>
        <script>
            $(function(){
                $('body').addClass('bg-pat<?php echo rand(1,4);?>');
            })
        </script>
    </body>
</html>