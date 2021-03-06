<?php
/**
 * Copyright (c) 2012 3g4k.com.
 * All rights reserved.
 * @package     gkwp
 * @author      luofei614 <weibo.com/luofei614>
 * @copyright   2012 3g4k.com.
 */
require_once file_exists(get_stylesheet_directory() . '/lib/core.php') ? get_stylesheet_directory() . '/lib/core.php' : get_template_directory() . '/lib/core.php';
if (!isset($content_width)) $content_width = gk_config('content_width');
add_action('after_setup_theme', 'gk_setup');
if (!function_exists('gk_setup')) {
    function gk_setup() {
        //支持语言包
        load_theme_textdomain('gkwp', __PROOT__ . '/languages');
        $languages = gk_config('languages');
        
        foreach ($languages as $name => $path) {
            load_theme_textdomain($name, $path);
        }
        //设置主题支持
        $theme_supports = gk_config('theme_support');
        
        foreach ($theme_supports as $key => $support) {
            is_numeric($key) ? add_theme_support($support) : add_theme_support($key, $support);
        }
        register_nav_menus(gk_config('register_nav_menus'));
        //设置缩略图
        $thumbnails = gk_config('thumbnails');
        
        foreach ($thumbnails as $name => $setting) {
            set_post_thumbnail_size($setting[0], $setting[1], $setting[2]);
            add_image_size($name, $setting[0], $setting[1], $setting[2]);
        }
        //支持 register_default_headers
        $register_default_headers_config = gk_config('register_default_headers');
        if (!empty($register_default_headers_config)) register_default_headers($register_default_headers_config);
        //加载插件
        $plugins=gk_config('plugins');
        foreach($plugins as $plugin){
            if(is_array($plugin)){
                    if($plugin[0]) include get_gk_file('plugins/'.$plugin[1]);
            }else{
                 include get_gk_file('plugins/'.$plugin);
            }
        }
        include get_gk_file('lib/walker.php');
        add_filter('template_include','gk_template_include');
    }
}
if(!function_exists('gk_template_include')){
function gk_template_include($template){
    //分类模版
    $cat_tpl=gk_config('category_tpl');
    if(empty($cat_tpl) || !is_category()) return $template;
    $obj=get_queried_object();
    if($tpl_path=gk_get_cat_tpl($obj)){
        $template=$tpl_path;
    }
    return $template;
}
}
if(!function_exists('gk_get_cat_tpl')){
    function gk_get_cat_tpl($obj){
        $cat_tpl=gk_config('category_tpl');
        //进入循环判断
        if(isset($cat_tpl[$obj->term_id])){
            return get_gk_file($cat_tpl[$obj->term_id]);
        }
        if(isset($cat_tpl[$obj->parent])){
            return get_gk_file($cat_tpl[$obj->parent]);
        }
        if('0'==$obj->parent){
            return false;
        }
        $obj=get_term($obj->parent,'category');
        if(is_wp_error($obj)) return false;
        return gk_get_cat_tpl($obj);
    }
}
//注册侧栏
add_action('widgets_init', 'gk_widgets_init');
if (!function_exists('gk_widgets_init')) {
    function gk_widgets_init() {
        //注册系统widgets
        require_once get_gk_file('lib/widgets.php');
        //替换默认的widget
        $widgets=array('Widget_Recent_Comments','Widget_Recent_Comments','Widget_Pages','Widget_Links','Widget_Search','Widget_Archives','Widget_Meta','Widget_Text','Widget_Categories','Widget_Recent_Posts','Widget_RSS','Widget_Tag_Cloud','Nav_Menu_Widget','Nav_Menu_Widget','Widget_Calendar');
        foreach($widgets as $widget){
            unregister_widget('WP_'.$widget);
            register_widget('GK_'.$widget);
        }
        //新增小工具
        register_widget('GK_cat_post');
        //注册边栏
        $sidebars = gk_config('sidebar');
        foreach ($sidebars as $sidebar) {
            unset($sidebar['default']);
            register_sidebar($sidebar);
        }
        if(gk_config('sidebar_debug')){
            //边栏时时调试
            gk_set_sidebar_default();
        }
    }
}
//菜单默认显示首页
add_filter('wp_page_menu_args', 'gk_page_menu_args');
if (!function_exists('gk_page_menu_args')) {
    function gk_page_menu_args($args) {
        $args['show_home'] = true;
        
        return $args;
    }
}
//选择主题后初始化
add_action('after_switch_theme', 'gk_set_sidebar_default');
if (!function_exists('gk_set_sidebar_default')) {
    function gk_set_sidebar_default() {
        global $_wp_sidebars_widgets;
        //初始化widget
        $widgets = gk_config('widget');
        foreach ($widgets as $name => $setting) {
            $id = 102;
            $option = get_option('widget_'.$name);
            if (!$option) $option = array();
            if (!isset($option[$id]) || gk_config('sidebar_debug')) {
                $option[$id] = $setting;
                update_option('widget_'.$name, $option);
            }
        }
        //初始化边栏
        $sidebars_option = wp_get_sidebars_widgets();
        $update_sidebar = true;
        $sidebars = gk_config('sidebar');
        
        foreach ($sidebars as $sidebar) {
            if (isset($sidebar['default']) && (!isset($sidebars_option[$sidebar['id']]) || gk_config('sidebar_debug')) ) {
                $sidebars_option[$sidebar['id']] = array_map(create_function('$v', 'return $v.\'-102\';'),explode(',', $sidebar['default']));
                $update_sidebar = true;
            }
        }
        if ($update_sidebar) {
            $_wp_sidebars_widgets = $sidebars_option;
            wp_set_sidebars_widgets($sidebars_option);
        }
    }
}
//分页
if (!function_exists('gk_page')) {
    function gk_page($query_string) {
        global $posts_per_page, $paged;
        $my_query = new WP_Query($query_string . "&posts_per_page=-1");
        $total_posts = $my_query->post_count;
        $total = gk_config('page_debug')?100:ceil($total_posts / $posts_per_page);
        $s=gk_config('page_num');//显示个数
        if (empty($paged)) $paged = 1;
        if($total<2) return ;
            $start = 1;
            $end = $total;

            $q = ceil(($s-1)/2);
            $h = $s - $q - 1;

            if($paged<=ceil($total/2)){
                $start = $paged - $q;
                if($start<1){
                    $h += ( 1 - $start );
                    $start = 1;
                }
                $end = $paged + $h;
                if($end>$total){
                    $end = $total;
                }
            }else{
                $end = $paged + $h;
                if($end>$total){
                    $q += $end - $total;
                    $end = $total;
                }
                $start = $paged - $q;
                if($start<1){
                    $start = 1;
                }
            }
            echo '<div class="'.gk_config('page_class').'"><ul>';
            if($paged==1){
                echo  '<li><a class="first">上一页</a></li>';
            }else{
                echo '<li><a class="first" href="'.sprintf($url,1).'">上一页</a></li>';
            }
            for($i=$start;$i<=$end;$i++){
                if($i==$paged){
                    echo  '<li class="active"><a class="cur">'.$i.'</a></li>';
                }else{
                    echo '<li><a href="'.get_pagenum_link($i) .'">'.$i.'</a></li>';
                }
            }

            if($paged==$total){
                echo  '<li><a class="last">下一页</a></li>';
            }else{
                echo  '<li><a class="last" href="'.get_pagenum_link($total).'">下一页</a></li>';
            }
            echo "</ul></div>\n";
    }
}
//加载css
if (!function_exists('gk_load_css')) {
    function gk_load_css() {
        //注册css
        gk_register_styles();
        $css_config = gk_config('css');
        
        foreach ($css_config as $css) {
            $before = '';
            $after = '';
            if (is_array($css)) {
                //支持 is_page(), is_home() 等函数判断加载
                if (is_bool($css[0])) {
                    if (!$css[0]) continue;
                } else {
                    $before = '<!--[if ' . $css[0] . ']>';
                    $after = '<![endif]-->';
                }
                $css = $css[1];
            }
            $pathinfo = pathinfo($css);
            $ext = isset($pathinfo['extension']) ? $pathinfo['extension'] : '';
            if ('css' != $ext) {
                //列队名称载入
                if (wp_style_is($css)) continue; //如果已经有列队，则不会重复载入
                global $wp_styles;
                $css_url = $wp_styles->registered[$css]->src;
            } else {
                $css_url = filter_var($css, FILTER_VALIDATE_URL) ? $css : get_gk_url($css);
            }
            echo $before . PHP_EOL;
            echo '<link rel="stylesheet" type="text/css" href="' . $css_url . '" media="all">' . PHP_EOL;
            echo $after . PHP_EOL;
        }
    }
}
if (!function_exists('gk_register_styles')) {
    function gk_register_styles() {
        $register_css = gk_config('register_css');
        
        foreach ($register_css as $name => $r_css) {
            if (!filter_var($r_css, FILTER_VALIDATE_URL)) $r_css = get_gk_url($r_css);
            wp_deregister_style($name);
            wp_register_style($name, $r_css);
        }
    }
}
//注册script
add_action('wp_enqueue_scripts', 'gk_register_scripts');
if (!function_exists('gk_register_scripts')) {
    function gk_register_scripts() {
        $register_js = gk_config('register_js');
        
        foreach ($register_js as $name => $js) {
            if (!filter_var($js, FILTER_VALIDATE_URL)) $js = get_gk_url($js);
            wp_deregister_script($name);
            wp_register_script($name, $js);
        }
    }
}
//加载js
if (!function_exists('gk_load_js')) {
    function gk_load_js() {
        $js_config = gk_config('js');
        
        foreach ($js_config as $js) {
            $before = '';
            $after = '';
            if (is_array($js)) {
                //支持 is_page(), is_home() 等函数判断加载
                if (is_bool($js[0])) {
                    if (!$js[0]) continue;
                } else {
                    $before = '<!--[if ' . $js[0] . ']>';
                    $after = '<![endif]-->';
                }
                $js = $js[1];
            }
            $pathinfo = pathinfo($js);
            $ext = isset($pathinfo['extension']) ? $pathinfo['extension'] : '';
            if ('js' != $ext) {
                //用列队名称载入
                if (wp_script_is($js)) continue; //如果已经载入，则不再重复载入
                global $wp_scripts;
                $js_url = $wp_scripts->registered[$js]->src;
            } else {
                $js_url = filter_var($js, FILTER_VALIDATE_URL) ? $js : get_gk_url($js);
            }
            echo $before . PHP_EOL;
            echo '<script type="text/javascript" src="' . $js_url . '"></script>' . PHP_EOL;
            echo $after . PHP_EOL;
        }
    }
}
//主题选项
add_action('admin_init', 'gk_theme_options_init');
add_action('admin_menu', 'gk_theme_options_add_page');
if (!function_exists('gk_theme_options_init')) {
    function gk_theme_options_init() {
        //注册设置组
        $theme_options = gk_config('theme_options');
        
        foreach ($theme_options as $name => $setting) {
            $callback = '';
            if (is_string($setting)) $setting = array(
                $setting
            );
            if (isset($setting[1])) $callback = $setting[1];
            register_setting($name, $name, $callback);
        }
    }
}
if (!function_exists('gk_theme_options_add_page')) {
    function gk_theme_options_add_page() {
        $theme_options = gk_config('theme_options');
        
        foreach ($theme_options as $name => $setting) {
            if (is_string($setting)) $setting = array(
                $setting
            );
            add_theme_page($setting[0], $setting[0], 'edit_theme_options', $name, 'gk_theme_options_show_page');
        }
        //加载后台静态文件
        add_action('admin_print_scripts', 'gk_admin_scripts');
        add_action('admin_print_styles', 'gk_admin_styles');
    }
}
if (!function_exists('gk_theme_options_show_page')) {
    function gk_theme_options_show_page() {
        $option_name = basename($_GET['page']);
        include get_gk_file('options/' . $option_name . '.php');
    }
}
if (!function_exists('gk_admin_scripts')) {
    function gk_admin_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('media-upload');
        wp_enqueue_script('thickbox');
        wp_enqueue_script('gk-upload', get_gk_url('static/js/gk-upload.js'));
    }
}
if (!function_exists('gk_admin_styles')) {
    function gk_admin_styles() {
        wp_enqueue_style('thickbox');
    }
}
if (!function_exists('gk_upload')) {
    function gk_upload($name, $value, $class = 'gk_upload', $label = '选择文件') {
        echo '<input name="' . $name . '" type="text" id="' . $name . '" value="' . $value . '" class="' . $class . '" /><input type="button"  name="left_upload_button" class="gk_upload_button" value="' . $label . '"  />';
    }
}
//显示分类的复选框形式，如果要显示下拉菜单形式可以用函数wp_dropdown_categories
if(!function_exists('gk_show_categorie_checkbox')){
    function gk_show_categorie_checkbox($input_name,$attr,$selected=array(),$term_id=0){
             //层次处理
          static  $cats=array();
          if(empty($cats)){
            $categorys=get_categories();
            foreach ($categorys as $obj) {
               $cats[$obj->parent][$obj->term_id]=$obj->name;
            }
          }
      echo '<ul '.$attr.'>';
          foreach($cats[$term_id] as $id=>$name){
                $s=in_array($id, $selected)?'checked="checked"':'';
                echo '<li><input type="checkbox" name="'.$input_name.'" value="'.$id.'" '.$s.'>'.$name;
                if(isset($cats[$id])){
                    gk_show_categorie_checkbox($input_name,$attr,$selected,$id);
                }
                echo '</li>';
          }
      echo '</ul>';
    }
}

//评论
if (!function_exists('gk_comments')) {
    function gk_comments($comment, $args, $depth) {
        $GLOBALS['comment'] = $comment;
        include get_gk_file('comment-list.php');
    }
}
//显示菜单函数
if (!function_exists('gk_nav_menu')) {
    function gk_nav_menu($location) {
        $menus = gk_config('nav');
        $args = $menus[$location];
        $args['theme_location'] = $location;
        wp_nav_menu($args);
    }
}
//实例化单页walk
add_filter('wp_page_menu_args', 'gk_wp_page_menu_args');
if (!function_exists('gk_wp_page_menu_args')) {
    function gk_wp_page_menu_args($args) {
        $args['walker'] = new Gk_Page_Walker;
        
        return $args;
    }
}
add_filter('comment_form_defaults', 'gk_comment_form_defaults');
function gk_comment_form_defaults($defaults) {
    
    return wp_parse_args(array(
        'comment_field' => '<div class="comment-form-comment control-group"><label class="control-label" for="comment">' . _x('评论内容', 'noun', 'gkwp') . '</label><div class="controls"><textarea class="span6" id="comment" name="comment" rows="8" aria-required="true"></textarea></div></div>',
        'comment_notes_before' => '',
        'comment_notes_after' => '<div class="form-allowed-tags control-group"><label class="control-label">' . sprintf(__('您可以使用这些 <abbr title="HyperText Markup Language">HTML</abbr> 标签和属性: %s', 'gkwp') , '</label><div class="controls"><pre>' . allowed_tags() . '</pre></div>') . '</div>',
        'title_reply' => '<legend>' . __('评论', 'gkwp') . '</legend>',
        'title_reply_to' => '<legend>' . __('回复 %s', 'gkwp') . '</legend>',
        'must_log_in' => '<div class="must-log-in control-group controls">' . sprintf(__('你必须先 <a href="%s">登录</a>', 'gkwp') , wp_login_url(apply_filters('the_permalink', get_permalink(get_the_ID())))) . '</div>',
        'logged_in_as' => '<div class="logged-in-as control-group controls">' . sprintf(__('已登录 <a href="%1$s">%2$s</a>. <a href="%3$s" title="退出">退出?</a>', 'gkwp') , admin_url('profile.php') , wp_get_current_user()->display_name, wp_logout_url(apply_filters('the_permalink', get_permalink(get_the_ID())))) . '</div>',
    ) , $defaults);
}
