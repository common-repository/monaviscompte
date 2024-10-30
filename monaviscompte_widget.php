<?php

include_once plugin_dir_path( __FILE__ ).'/includes/constants.php';

class monaviscompte_Widget extends WP_Widget
{
    public function __construct()
    {
        parent::__construct('monaviscompte', 'monaviscompte', array('description' => __('This module allows you to display a monaviscompte widget on your website in a few seconds', 'monaviscompte')));
    }
    
    public function widget($args, $instance)
    {
    	$itemId = get_option(MONAVISCOMPTE_ITEM_ID_FIELD_NAME);
    	$accessKey = get_option(MONAVISCOMPTE_ACCESS_KEY_FIELD_NAME);
    	
    	echo '<div id="widget-'.$itemId.'" class="widget">';
    	echo 		'<script type="text/javascript" src="https://www.monaviscompte.fr/widget/?id='.$itemId.'&div=widget-'.$itemId.'&public_key='.$accessKey.'"></script>';
			echo '</div>';
    }
}