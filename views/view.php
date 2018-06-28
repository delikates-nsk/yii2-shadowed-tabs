<?php

use delikatesnsk\shadowedtabs\ShadowedTabsAsset;

/**
 * @var $this \yii\web\View
 * @var $widget \delikatesnsk\shadowedtabs\ShadowedTabsWidget
 * @var $tabsHtmlData string
 * @var $ajaxParamsStr string
 * @var $tabsContentHtmlData string
 */

ShadowedTabsAsset::register($this);
$widget = $this->context;

?>
    <div class="tabbed-container <?= $widget->skin; ?>">
        <?php
        if ( $widget->position == ShadowedTabsWidget::POS_TOP ) {
            ?>
            <div class="row">
                <div class="col-md-12">
                    <?= $tabsHtmlData; ?>
                </div>
            </div>
            <?php
        }
        ?>
        <div class="row">
            <div class="col-md-12">
                <?= $tabsContentHtmlData; ?>
            </div>
        </div>
        <?php
        if ( $widget->position == ShadowedTabsWidget::POS_BOTTOM ) {
            ?>
            <div class="row">
                <div class="col-md-12">
                    <?= $tabsHtmlData; ?>
                </div>
            </div>
            <?php
        }
        ?>
    </div>
<?php
$id = ( isset( $widget->id ) && $widget->id != "" ? "#".$widget->id : ".tabbed");

$js = "$(document).ready(function() {
    if ( $('".$id." .slide-arrow').length > 0 ) {
        var \$wndLeft = $('".$id." .overflow-hide').offset().left;
        var \$wndWidth = $('".$id." .overflow-hide').outerWidth();
        var \$lastTabLeft = $('".$id." li:first').offset().left;
        var \$lastTabWidth = $('".$id." li:first').outerWidth();
        //last tab is visible?
        if ( ( \$lastTabLeft + \$lastTabWidth ) > ( \$wndLeft + \$wndWidth ) ) {
            //no, show right scroll arrow is this not showed
            if ( $('".$id." .slide-arrow.right').hasClass('hide') ) {
             $('".$id." .slide-arrow.right').removeClass('hide');
         } 
        }
    }

    //prepare tab click
    $('".$id." li').on('click', function(){
        if ( typeof( $(this).attr('disabled') ) == 'undefined' ) {
            //make current active tab as inactive
            $('".$id." li.active').removeClass('active');
            //make clicked tab as active
            $(this).addClass('active');
            //show content of clicked tab
            if ( $.isArray( \$idx ) && \$idx.length == 2 ) {
                $(".$id." .tab-content:not(.hide)').addClass('hide');
                $(".$id." .tab-content.'+$(this).attr('data-id')).removeClass('hide');
            }
";

//call user function before send ajax-request
if ( is_array( $widget->ajax ) && isset( $widget->ajax['before'] ) ) {
$js .= "
            var \$beforeCallbackFunc = ".$widget->ajax['before'].";
            var \$tab = {
                id: $(this).attr('data-id'),
                index: $(this).index,
                label: $(this).children('span').html(),
                visible: true,
                selected: true,
                content: $('".$id." .tab-content '+$(this).attr('data-id')).html();
            };
            \$beforeCallbackFunc(node);    
";
}

//send ajax-request to get selected tab content
if ( is_array( $widget->ajax ) ) {
    if ( $widget->ajax['showIndicator'] ) {
        $js .= "            $('".$id." .tab-content '+$(this).attr('data-id')).html( '<div class=\"loader\"></div>' );
            ";
    }
    $js .= "
            var \$obj = $(this); 
            $.ajax({
                type: '".$widget->ajax['method']."',
                url: '".$widget->ajax['url']."',
                data: '".$ajaxParamsStr."'+$(this).attr('data-id'),
                error: function(req, text, error) { 
                    $('".$id." .tab-content '+\$obj.attr('data-id')).html( error );";
    if ( is_array( $widget->ajax ) && isset( $widget->ajax['after'] ) ) {
        $js .= "                    
            var \$afterCallbackFunc = ".$widget->ajax['after'].";
            var \$tab = {
                id: \$obj.attr('data-id'),
                index: \$obj.index,
                label: \$obj.children('span').html(),
                visible: true,
                selected: true,
                content: $('".$id." .tab-content '+\$obj.attr('data-id')).html();
            };
            \$afterCallbackFunc(node);    
        ";
    }
    $js .=  "          },
                success: function (data) {
                    $('".$id." .tab-content '+\$obj.attr('data-id')).html( $.parseJSON( data ) );";

    if ( is_array( $widget->ajax ) && isset( $widget->ajax['after'] ) ) {
        $js .= "                    
            var \$afterCallbackFunc = ".$widget->ajax['after'].";
            var \$tab = {
                id: \$obj.attr('data-id'),
                index: \$obj.index,
                label: \$obj.children('span').html(),
                visible: true,
                selected: true,
                content: $('".$id." .tab-content '+\$obj.attr('data-id')).html();
            };
            \$afterCallbackFunc(node);    
        ";
    }
    $js .= "            },
                datatype: 'json',
                async: true
            });
    ";
}

$js .= "            
        }
    });
    
    //prepare click on left arrow
    $('".$id." .slide-arrow.left').on('click', function(){";

//call user function before scroll tabs area
if ( is_array( $widget->scroll ) && isset( $widget->scroll['before'] ) ) {
    $js .= "        var \$beforeCallbackFunc = ".$widget->scroll['before'].";
            var \$direction = 'right'; //from left to right
            var \$tabs = [];
            var \$wndLeft = $('".$id." .overflow-hide').offset().left;
            var \$wndWidth = $('".$id." .overflow-hide').outerWidth();
            $('".$id." li').forEach( function() {
                var \$tabLeft = $(this).offset().left;
                var \$tabWidth = $(this).outerWidth();
                var \$tab = {
                    id: $(this).attr('data-id'),
                    index: $(this).index,
                    label: $(this).children('span').html(),
                    visible: ( \$tabLeft > \$wndLeft && ( \$tabLeft + \$tabWidth ) < ( \$wndLeft + \$wndWidth ) ),
                    selected: $(this).hasClass('active'),
                    content: $('".$id." .tab-content '+$(this).attr('data-id')).html();
                };
                \$tabs.push( \$tab );
            });           
            \$beforeCallbackFunc( direction, \$tabs );
    ";
}
$js .= "
        //scrolling tabs list from left to right
        $('".$id." .slide-window').animate({left: '+=145px'}, 200, function(){
            //after scrolling
            var \$wndLeft = $('".$id." .overflow-hide').offset().left;
            var \$firstTabLeft = $('".$id." li:last').offset().left;

            //first tab is visible?
            if (  \$firstTabLeft >= \$wndLeft ) {
                //yes, hide left arrow
                $('".$id." .slide-arrow.left').addClass('hide');
            }
            //show right arrow is this not showed early
            if ( $('".$id." .slide-arrow.right').hasClass('hide') ) {
                $('".$id." .slide-arrow.right').removeClass('hide');
            }";
//call user function after scroll tabs area
if ( is_array( $widget->scroll ) && isset( $widget->scroll['after'] ) ) {
    $js .= "        var \$afterCallbackFunc = ".$widget->scroll['after'].";
            var \$direction = 'right'; //from left to right
            var \$tabs = [];
            var \$wndLeft = $('".$id." .overflow-hide').offset().left;
            var \$wndWidth = $('".$id." .overflow-hide').outerWidth();
            $('".$id." li').forEach( function() {
                var \$tabLeft = $(this).offset().left;
                var \$tabWidth = $(this).outerWidth();
                var \$tab = {
                    id: $(this).attr('data-id'),
                    index: $(this).index,
                    label: $(this).children('span').html(),
                    visible: ( \$tabLeft > \$wndLeft && ( \$tabLeft + \$tabWidth ) < ( \$wndLeft + \$wndWidth ) ),
                    selected: $(this).hasClass('active'),
                    content: $('".$id." .tab-content '+$(this).attr('data-id')).html();
                };
                \$tabs.push( \$tab );
            });           
            \$afterCallbackFunc( direction, \$tabs );
    ";
}
$js .= "
        });
    });
    
    //prepare click on right arrow
    $('".$id." .slide-arrow.right').on('click', function(){
        //scrolling tabs list from right to left
        $('".$id." .slide-window').animate({left: '-=145px'}, 200, function(){

        var \$wndLeft = $('".$id." .overflow-hide').offset().left;
        var \$wndWidth = $('".$id." .overflow-hide').outerWidth();
        var \$lastTabLeft = $('".$id." li:first').offset().left;
        var \$lastTabWidth = $('".$id." li:first').outerWidth();

        //last tab is visible?
        if ( ( \$lastTabLeft + \$lastTabWidth ) <= ( \$wndLeft + \$wndWidth ) ) {
            //yes, hide right arrow
            $('".$id." .slide-arrow.right').addClass('hide');
        }
        //show left arrow is this not showed early
        if ( $('".$id." .slide-arrow.left').hasClass('hide') ) {
            $('".$id." .slide-arrow.left').removeClass('hide');
        }
    });
});
";


$js .= "
});
";
$this->registerJs( $js );
