<?php
/**
 * @package Modern Snowflakes
 */
/*
Plugin Name: Modern Snowflakes
Plugin URI: http://github.com/ramuuns/wp-snowflakes
Description: Used by millions[1], this awesome plugin allows you to rid your site from those pesky visitors by showning them snow. Oh and we have options for the snow. It's using weather APIs to show snow only when it's actually snowing if you're into that sort of thing. Also there's what we call the nuclear option, but you have to try it out to see what that's like. <br/><br/>[1] - probably an overstatement
Version: 1.0.0
Author: Ramuns Usovs
Author URI: http://ramuuns.com/
License: MIT
Text Domain: snowflakes
*/


defined('ABSPATH') or die("No script kiddies please!");


if ( is_admin() ) {
   add_action( 'admin_menu', 'snowflakes_menu' );
   add_action( 'admin_init', 'snowflakes_admin_init');
}

function snowflakes_menu() {
    add_options_page(
        'Settings Admin', 
        'Modern Snowflakes', 
        'manage_options', 
        'snowflakes-settings', 
        'snowflakes_admin_page'
    );
}


function snowflakes_admin_page() {
?>
<div class="wrap">
    <?php screen_icon(); ?>
    <h2>Modern Snowflakes</h2>
    <form method="post" action="options.php">
        <?php
             settings_fields( 'snowflake_options' );
             do_settings_sections('snowflakes-settings');
             submit_button(); 
        ?>
    </form>
</div>
<?php
    snowflakes_admin_js();
}

function snowflakes_admin_init() {
    register_setting('snowflake_options', 'snowflake_settings', 'snowflake_sanitize');
    add_settings_section("ss_id","Snowflake settings", "print_section_info", "snowflakes-settings");
    add_settings_field("on_when", "On when", "on_when_cb", "snowflakes-settings", "ss_id");
    //add_settings_field("location","Location","location_cb","snowflakes-settings","ss_id");

    add_settings_field("amount", "Amount of snowflakes", "amount_cb", "snowflakes-settings", "ss_id");
    add_settings_field("reverse_direction", "Reverse direction", "reverse_direction_cb", "snowflakes-settings", "ss_id");
    add_settings_field("nuclear_mode", "Nuclear mode", "nuclear_mode_cb", "snowflakes-settings", "ss_id");
}

function snowflake_sanitize($input) {
    $ok_input = array();
    $valid_on_when = array("never","geo","always");
    if ( isset($input["on_when"]) && in_array($input['on_when'], $valid_on_when) ) {
        $ok_input['on_when'] = $input['on_when'];
    } else {
        $ok_input['on_when'] = $valid_on_when[0];
    }
    if ( isset($input["nuclear_mode"]) && $input["nuclear_mode"] ) {
        $ok_input["nuclear_mode"] = true;
    } else {
        $ok_input["nuclear_mode"] = false;
    }
    if ( isset($input['amount']) && (int)$input['amount'] == $input['amount'] ) {
        $ok_input['amount'] = $input['amount'];
    } else {
        $ok_input['amount'] == "auto";
    }
    if ( $ok_input['on_when'] == 'geo' && isset($input['location']) && isset($input['location']['lat']) && isset($input['location']['lng']) ) {
        $ok_input['location']['lat'] = (float)$input['location']['lat'];
        $ok_input['location']['lng'] = (float)$input['location']['lng'];
    } else if ( $ok_input['on_when'] == 'geo' ) {
        $ok_input['on_when'] = 'always';
    }
    if ( isset($input['reverse_direction']) ) {
        $ok_input['reverse_direction'] = true;
    } else {
        $ok_input['reverse_direction'] = false;
    }
    return $ok_input;
}

function print_section_info() {
    ?>
    Modify to settings to decide when should your visitors see the snowflakes
    <?php
}

function on_when_cb(){
    $opts = get_option("snowflake_settings");
    $val = isset($opts['on_when']) ? $opts['on_when'] : "never";
    ?>
        <label><input type="radio" class="on-when" name="snowflake_settings[on_when]" value="never" <?php if( $val == "never" ) { ?> checked <?php } ?> > Never</label><br>
        <!-- label><input type="radio" class="on-when" name="snowflake_settings[on_when]" value="geo" <?php if( $val == "geo" ) { ?> checked <?php } ?> > Based on geo coordingates (if it's snowing there)</label><br -->
        <label><input type="radio" class="on-when" name="snowflake_settings[on_when]" value="always" <?php if( $val == "always" ) { ?> checked <?php } ?> > Always</label>
    <?php 
}

function amount_cb() {
    $opts = get_option("snowflake_settings");
    $val = isset($opts['amount']) ? $opts['amount'] : 'auto';
    ?>
        <label><input type="radio" name="kinda_amount" value="auto" <?php if( $val == "auto" ) { ?> checked <?php } ?> > Auto</label><br>
        <label><input type="radio" name="kinda_amount" value="manual" <?php if( $val != "auto" ) { ?> checked <?php } ?> > <div style="display:inline-block;position:relative;"><input type="number" value="<?= (int)$val ?>" id="ss-amount" name="snowflake_settings[amount]" ><div id="amount_dis_overlay" style="position:absolute;top:0;left:0;bottom:0;right:0;display:none;"></div></div></label>
    <?php
}

function nuclear_mode_cb() {
    $opts = get_option("snowflake_settings");
    $val = isset($opts['nuclear_mode']) ? $opts['nuclear_mode'] : false;
    ?><input type="checkbox" name="snowflake_settings[nuclear_mode]" value="1" <?php if( $val == true ) { ?> checked <?php } ?> >
    <?php
}

function location_cb() {
    $opts = get_option("snowflake_settings");
    $val = isset($opts['location']) ? $opts['location'] : array("lat"=>'', "lng"=>'');
    ?>
    <label>Latitude <input type="text" value="<?= $val['lat'] ?>" name="snowflake_settings[location][lat]" class="location" id="location-lat"  placeholder="12.1234"></label><br>
    <label>Longitude <input type="text" value="<?= $val['lng'] ?>" placeholder="12.1234" name="snowflake_settings[location][lng]" id="location-lng" class="location"></label><br>
    <button id="set_current_location" class="button location">Set from my location</button>
    <?php
}

function reverse_direction_cb() {
    $opts = get_option("snowflake_settings");
    $val = isset($opts['reverse_direction']) ? $opts['reverse_direction'] : false;
    ?>
    <input type="checkbox" name="snowflake_settings[reverse_direction]" value="1" <?= $val?"checked":"" ?> >
    <?php
}

function snowflakes_admin_js() {
    ?>
    <script type="text/javascript">
        (function($){
            "use strict";
            $(function(){
                function do_shit_based_on_radio( ){

                    var val = $("input[name=kinda_amount]:checked").val();
                    $("#ss-amount")[0].disabled = val === "auto";
                    $("#amount_dis_overlay")[val==="auto"?"show":"hide"]();
                };

                function check_a_radio(name, value) {
                    $("input[name="+name+"]").each(function(){
                        this.checked = $(this).val() === value;
                    });
                }

                do_shit_based_on_radio();

                $("#amount_dis_overlay").on("click", function(){
                    $(this).hide();
                    $("#ss-amount")[0].disabled = false;
                    check_a_radio("kinda_amount", "manual");
                    $("#ss-amount")[0].focus();
                });

                $("input[name=kinda_amount]").on("click", do_shit_based_on_radio);

                if ( navigator.geolocation ) {
                    $("#set_current_location").on("click", function(){
                        this.disabled = true;
                        navigator.geolocation.getCurrentPosition(function(position){
                            this.disabled = false;
                            $("#location-lat").val(position.coords.latitude);
                            $("#location-lng").val(position.coords.longitude);
                        });
                        return false;
                    });
                } else {
                    $("#set_current_location").hide();
                }

                function do_shit_based_on_when() {
                    var disable = $(".on-when:checked").val() !== "geo";
                    $(".location").each(function(){
                        this.disabled = disable;
                    });
                }

                 do_shit_based_on_when();

                $(".on-when").on("click", do_shit_based_on_when);
            });
        })(jQuery);
    </script>
    <?php
}


add_action('wp_footer', 'do_snowflakes');

function do_snowflakes(){
    $opts = get_option("snowflake_settings");
    if ( !isset($opts['on_when']) || $opts['on_when'] == "never" ) {
        return;
    }
    $amount = null;
    if ( $opts['on_when'] == "geo" ) {
        //$data = file_get_contents('http://api.wunderground.com/auto/wui/geo/GeoLookupXML/index.xml?query='.$opts['location']['lat'].','.$opts['location']['lng']);
        //var_dump($data);
        exit;
        if ( $opts['amount'] == "auto" ) {
            //do shit
        } else {
            $amount = $opts['amount'];
        }
    } else {
        $amount = !isset($opts['amount']) || $opts['amount'] == "auto" ? 250 : $opts['amount'];
    }


    ?>
    <script type="text/javascript" src="<?= plugins_url( 'snowflakes.min.js', __FILE__ ); ?>"></script>
    <script type="text/javascript">
        window.snowflakes({amount: <?= $amount ?>, invertDirection: <?= isset($opts['reverse_direction'])&&$opts['reverse_direction']?"true":"false" ?>, nuclearMode: <?=$opts['nuclear_mode']?"true":"false"?>});
    </script>
    <?php
}