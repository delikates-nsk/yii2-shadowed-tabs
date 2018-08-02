<?php
namespace delikatesnsk\shadowedtabs;

class ShadowedTabsWidget extends \yii\base\Widget
{
    const POS_TOP = 'top';
    const POS_BOTTOM = 'bottom';

    //Id for tabs container  (ATTENTION! Required if you have more than one ShadowedTabsWidget at page!)
    public $id = false;

    //skin
    public $skin = 'skin-light-gray'; //skin

    //tabs area position
    public $position = self::POS_TOP;

    //array with next options for scrolling tabs area arrows
    public $scroll = null;
    // [
    //  'visible' => true|false|'auto' //show/hide scroll arrows, default 'auto'
    //  'before' => '', //javascript callback function that will be runned before tabs area scrolled
    //    function(direction, tabs) {
    //       console.log( direction ); //'right' or 'left'
    //       //tabs is array of all tabs
    //       tabs.forEach(function(){
    //           console.log( $(this).id );
    //           console.log( $(this).index );
    //           console.log( $(this).visible );
    //           console.log( $(this).label );
    //           console.log( $(this).selected );
    //           console.log( $(this).content );
    //       });
    //    }
    //   'after' => '', //javascript callback function that will be runned after tabs area scrolled
    //    function(direction, tabs) {
    //       ... see example on 'before'
    //    }
    // ]

    //params for ajax-request to download selected tab content, it's global params for all tabs
    public $ajax = null;
    // [
    //      'url' => '', //url for ajax request
    //      'method' => 'post', //send method - 'post' or 'get', default 'post'
    //      'showIndicator' => true, //show or not ajax loading indicator
    //      'params' => [
    //                     'param1' => 'value1',  // <-- Your additional params (if you need)
    //                     'param2' => 'value1',
    //                     'param3' => 'value1',
    //                     'tab_id' => '%tabId' // <-- %tabId will be replaced to id of selected tab,
    //                                          // you can change 'tab_id' key to any other, default key is 'id'
    //                  ],
    //      'before' => '', //javascript callback function that will be runned before send ajax-request
    //                  function(tab) {
    //                      console.log( tab.id );
    //                      console.log( tab.index );
    //                      console.log( tab.visible );
    //                      console.log( tab.label );
    //                      console.log( tab.selected );
    //                      console.log( tab.content );
    //                  }
    //      'after' => '', //javascript callback function that will be runned after send ajax-request and receive answer
    //                  function(tab) {
    //                      console.log( tab.id );
    //                      console.log( tab.index );
    //                      console.log( tab.visible );
    //                      console.log( tab.label );
    //                      console.log( tab.selected );
    //                      console.log( tab.content );
    //                  }
    // ]

    //array of arrays of tabs
    public $tabs = [];
    //[
    //  [
    //      'id' => '', //tab id
    //      'label' => '', //tab label
    //      'selected' => true|false, //when true, tab will be selected. Only one tab can be selected! By default first tab is selected always.
    //      'content' => '', //content that will be shown when you select a tab
    //      'ajax' => '', //params for ajax-request to download selected tab content (see global $ajax params), attention! overwrite global $ajax params!
    //  ],
    //  [
    //      ...
    //  ],
    //  ...
    //]

    private $availableSkins = [
        'skin-light-gray', 'skin-silver', 'skin-turquoise', 'skin-emerald', 'skin-peter-river', 'skin-amethyst',
        'skin-wet-asphalt', 'skin-sun-flower', 'skin-carrot', 'skin-alizarin', 'skin-graphite', 'skin-concrete',
        'skin-green-sea', 'skin-nephritis', 'skin-belize-hole', 'skin-wisteri', 'skin-midnight-blue', 'skin-orange',
        'skin-pumpkin', 'skin-pomegranate', 'skin-asbestos', 'skin-dodgerblue', 'skin-gray-black', 'skin-black-glass'
    ];

    private $renderedTabsData = '';
    private $renderedTabsContentData = '';

    //Only one tab will ...marked as selected
    private function markOneTabAsSelected() {
        if ( is_array( $this->tabs ) && count( $this->tabs ) > 0 ) {
            $exist = false;
            for( $i = 0; $i < count( $this->tabs ); $i++ ) {
                if ( isset( $this->tabs[$i]['selected'] ) && $this->tabs[$i]['selected'] ) {
                    if ( $exist ) {
                        $this->tabs[$i]['selected'] = false;
                    } else { $exist =  true; }
                }
                if ( !isset( $this->tabs[$i]['id'] ) || trim( $this->tabs[$i]['id'] ) == '' ) {
                    $this->tabs[$i]['id'] = 'tab'.$i;
                }
                if ( isset( $this->tabs[$i]['ajax'] ) && is_array( $this->tabs[$i]['ajax'] ) && isset( $this->tabs[$i]['ajax']['url'] ) && trim( $this->tabs[$i]['ajax']['url'] ) != '' ) {
                    if ( !isset( $this->tabs[$i]['ajax']['method'] ) || ( $this->tabs[$i]['ajax']['method'] != 'post' || $this->tabs[$i]['ajax']['method'] != 'get' ) ) {
                        $this->tabs[$i]['ajax']['method'] = 'post';
                    }
                    if ( !isset( $this->tabs[$i]['ajax']['showIndicator'] ) || !is_bool( $this->tabs[$i]['ajax']['showIndicator'] ) ) {
                        $this->tabs[$i]['ajax']['showIndicator'] = true;
                    }
                    if ( isset( $this->tabs[$i]['ajax']['params'] ) && count( $this->tabs[$i]['ajax']['params'] ) > 0 ) {
                        $tabId = '';
                        $this->tabs[$i]['ajax']['ajaxParamsStr'] = '';
                        foreach( $this->tabs[$i]['ajax']['params'] as $key => $value ) {
                            $this->tabs[$i]['ajax']['ajaxParamsStr'] .= ( $this->tabs[$i]['ajax']['ajaxParamsStr'] != '' ? '&' : '' );
                            if ( $value != "%tabId" ) {
                                $this->tabs[$i]['ajax']['ajaxParamsStr'] .= $key.'='.$value;
                            } else {
                                $tabId = $key;
                            }
                        }
                        $this->tabs[$i]['ajax']['ajaxParamsStr'] .= ( $tabId != '' ? $tabId : 'id' ).'=';
                    }
                    if ( isset( $this->tabs[$i]['ajax']['before'] ) && !$this->isFunction( $this->tabs[$i]['ajax']['before'] ) ) {
                        unset( $this->tabs[$i]['ajax']['before'] );
                    }
                    if ( isset( $this->tabs[$i]['ajax']['after'] ) && !$this->isFunction( $this->tabs[$i]['ajax']['after'] ) ) {
                        unset( $this->tabs[$i]['ajax']['after'] );
                    }

                } else { $this->tabs[$i]['ajax'] = null; }

            }
            if ( !$exist ) {
                $this->tabs[0]['selected'] = true;
            }
        }
    }

    private function renderTabs() {
        $this->renderedTabsData = "
                <div class=\"tabbed".( $this->position == self::POS_BOTTOM ? " bottom" : "" )."\">";
        if ( ( !is_bool( $this->scroll['visible']) && $this->scroll['visible'] == 'auto' ) || $this->scroll['visible'] ) {
            $this->renderedTabsData .= "
                    <div class=\"slide-arrow left fa fa-arrow-circle-left hide\"></div>
                    <div class=\"slide-arrow right fa fa-arrow-circle-right hide\"></div>
                    ";
        }
        $this->renderedTabsData .= "
                    <div class=\"overflow-hide\">
                        <div class=\"slide-window\">
                            <ul>";

        for( $tabIndex = count( $this->tabs ) - 1; $tabIndex >=0; $tabIndex-- ) {
            $tab = $this->tabs[ $tabIndex ];
            $this->renderedTabsData .= "
                                <li data-id=\"".$tab['id']."\"".( isset( $tab['selected'] ) && $tab['selected'] ? " class=\"active\"" : "" )."><span>".( isset( $tab['label'] ) ? $tab['label'] : "&nbsp;" )."</span></li>
                                ";
        }
        $this->renderedTabsData .="
                            </ul>
                        </div>
                    </div>
                </div>
        ";
    }

    private function renderTabsContent() {
        foreach( $this->tabs as $tabIndex => $tab ) {
            $this->renderedTabsContentData .= "
                    <div class=\"tab-content ".$tab['id'].( !isset( $tab['selected'] ) || !$tab['selected'] ? " hide" : "" )."\">
                    ".( isset( $tab['content'] ) ? $tab['content'] : '' )."
                    </div>
                                ";
        }
    }

    private function isFunction( $code ) {
        $result = false;
        $code = preg_replace('/[\x0A]/', '', $code);
        $code = preg_replace('/[\x0D]/', '', $code);
        preg_match_all('/^function[\s]?\(.*?\)/', mb_convert_case( $code, MB_CASE_LOWER), $matches);
        if ( is_array( $matches ) && count( $matches ) == 1 && is_array( $matches[0] ) && count( $matches[0] ) == 1 ) {
            $matches = [];
            preg_match_all('/\{(.*?)\}/',$code, $matches);
            $result =  ( is_array( $matches ) && count( $matches ) > 0 && is_array( $matches[0] ) && count( $matches[0] ) == 1 );
            var_dump( $matches );
        }
        return $result;
    }

    private function initialize() {
        if ( !isset( $this->id ) || $this->id == "" ) {
            $this->id = 'tabs_'.time();
        }
        if ( !in_array( $this->skin, $this->availableSkins ) ) {
            $this->skin = $this->availableSkins[0];
        }
        if ( !in_array( $this->position, [ self::POS_TOP, self::POS_BOTTOM ] ) ) {
            $this->position = self::POS_TOP;
        }
        if ( !is_array( $this->scroll ) ) {
            $this->scroll = [];
        }
        if ( !isset( $this->scroll['visible'] ) || !is_bool( $this->scroll['visible'] ) ) {
            $this->scroll['visible'] = 'auto';
        }
        if ( isset( $this->scroll['before'] ) && !$this->isFunction( $this->scroll['before'] ) ) {
            unset( $this->scroll['before'] );
        }
        if ( isset( $this->scroll['after'] ) && !$this->isFunction( $this->scroll['after'] ) ) {
            unset( $this->scroll['after'] );
        }
        if ( is_array( $this->ajax ) && isset( $this->ajax['url'] ) && trim( $this->ajax['url'] ) != '' ) {
            if ( !isset( $this->ajax['method'] ) || ( $this->ajax['method'] != 'post' || $this->ajax['method'] != 'get' ) ) {
                $this->ajax['method'] = 'post';
            }
            if ( !isset( $this->ajax['showIndicator'] ) || !is_bool( $this->ajax['showIndicator'] ) ) {
                $this->ajax['showIndicator'] = true;
            }
            if ( isset( $this->ajax['params'] ) &&  count( $this->ajax['params'] ) > 0 ) {
                $tabId = '';
                $this->ajax['ajaxParamsStr'] = '';
                foreach( $this->ajax['params'] as $key => $value ) {
                    $this->ajax['ajaxParamsStr'] .= ( $this->ajax['ajaxParamsStr'] != '' ? '&' : '' );
                    if ( $value != "%tabId" ) {
                        $this->ajax['ajaxParamsStr'] .= $key.'='.$value;
                    } else {
                        $tabId = $key;
                    }
                }
                $this->ajax['ajaxParamsStr'] .= ( $tabId != '' ? $tabId : 'id' ).'=';
            }
            if ( isset( $this->ajax['before'] ) && !$this->isFunction( $this->ajax['before'] ) ) {
                unset( $this->ajax['before'] );
            }
            if ( isset( $this->ajax['after'] ) && !$this->isFunction( $this->ajax['after'] ) ) {
                unset( $this->ajax['after'] );
            }

        } else { $this->ajax = null; }
    }

    public function init()
    {
        parent::init();
        $this->initialize();
        $this->markOneTabAsSelected();
        $this->renderTabs();
        $this->renderTabsContent();
    }

    public function run()
    {
        return $this->render('view', [
            'tabsHtmlData' => $this->renderedTabsData,
            'tabsContentHtmlData' => $this->renderedTabsContentData,
        ] );
    }
}