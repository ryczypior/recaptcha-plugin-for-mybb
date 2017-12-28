<?php
/**
 * The MIT License (MIT)
 * 
 * Copyright (c) 2017 Łukasz Kodzis (Ryczypiór)
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software
 * and associated documentation files (the "Software"), to deal in the Software without restriction, 
 * including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, 
 * and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, 
 * subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all copies or substantial 
 * portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, 
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. 
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, 
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE 
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook('newthread_end', array('RecaptchaPlugin', 'newThread'));
$plugins->add_hook('newthread_do_newthread_start', array('RecaptchaPlugin', 'newPostDone'));
$plugins->add_hook('newreply_end', array('RecaptchaPlugin', 'newPost'));
$plugins->add_hook('newreply_do_newreply_start', array('RecaptchaPlugin', 'newPostDone'));
$plugins->add_hook('showthread_end', array('RecaptchaPlugin', 'quickReply'));
//$plugins->add_hook('private_send_end', array('RecaptchaPlugin', 'newPM'));
//$plugins->add_hook('private_send_do_send', array('RecaptchaPlugin', 'newPMDone'));

require_once('recaptcha_plugin/RecaptchaPlugin.php');

function recaptcha_plugin_info() {
    global $lang;
    $lang->load('recaptcha_plugin');
    return array(
        "name" => $lang->recaptcha_plugin_name,
        "description" => $lang->recaptcha_plugin_description,
        "website" => "https://github.com/ryczypior/recaptcha-plugin-for-mybb",
        "author" => "Ryczypiór",
        "authorsite" => "https://www.github.com/ryczypior",
        "version" => "1.0b",
        "compatibility" => "18*",
        "guid" => "",
        "language_file" => "recaptcha_plugin",
        "language_prefix" => "recaptcha_plugin_",
        "codename" => "recaptcha_plugin_for_mybb"
    );
}

function recaptcha_plugin_install() {
    global $mybb, $db, $lang;
    $lang->load('recaptcha_plugin');

    $gid = $db->insert_query('settinggroups', array(
        'name' => 'recaptcha_plugin',
        'title' => $db->escape_string($lang->recaptcha_plugin_settinggroups_title),
        'description' => $db->escape_string($lang->recaptcha_plugin_settinggroups_description),
            ));
    $position = 1;
    $cfg = array(
        array(
            'name' => 'recaptcha_plugin_enabled',
            'title' => $db->escape_string($lang->recaptcha_plugin_enabled),
            'description' => $db->escape_string($lang->recaptcha_plugin_enabled_description),
            'optionscode' => 'yesno',
            'value' => '1',
            'isdefault' => 1,
            'disporder' =>$position++,
            'gid' => $gid,
        ),
        array(
            'name' => 'recaptcha_plugin_privatekey',
            'title' => $db->escape_string($lang->recaptcha_plugin_privatekey),
            'description' => $db->escape_string($lang->recaptcha_plugin_privatekey_description),
            'optionscode' => 'text',
            'value' => '',
            'isdefault' => 1,
            'disporder' =>$position++,
            'gid' => $gid,
        ),
        array(
            'name' => 'recaptcha_plugin_publickey',
            'title' => $db->escape_string($lang->recaptcha_plugin_publickey),
            'description' => $db->escape_string($lang->recaptcha_plugin_publickey_description),
            'optionscode' => 'text',
            'value' => '',
            'isdefault' => 1,
            'disporder' =>$position++,
            'gid' => $gid,
        ),
        array(
            'name' => 'recaptcha_plugin_usergroups',
            'title' => $db->escape_string($lang->recaptcha_plugin_usergroups),
            'description' => $db->escape_string($lang->recaptcha_plugin_usergroups_description),
            'optionscode' => 'groupselect',
            'isdefault' => 1,
            'value' => '',
            'disporder' =>$position++,
            'gid' => $gid,
        ),
        array(
            'name' => 'recaptcha_plugin_threads_enabled',
            'title' => $db->escape_string($lang->recaptcha_plugin_threads_enabled),
            'description' => $db->escape_string($lang->recaptcha_plugin_threads_enabled_description),
            'optionscode' => 'yesno',
            'value' => '1',
            'isdefault' => 1,
            'disporder' =>$position++,
            'gid' => $gid,
        ),
        array(
            'name' => 'recaptcha_plugin_posts_enabled',
            'title' => $db->escape_string($lang->recaptcha_plugin_posts_enabled),
            'description' => $db->escape_string($lang->recaptcha_plugin_posts_enabled_description),
            'optionscode' => 'yesno',
            'value' => '1',
            'isdefault' => 1,
            'disporder' =>$position++,
            'gid' => $gid,
        ),
        array(
            'name' => 'recaptcha_plugin_pm_enabled',
            'title' => $db->escape_string($lang->recaptcha_plugin_pm_enabled),
            'description' => $db->escape_string($lang->recaptcha_plugin_pm_enabled_description),
            'optionscode' => 'yesno',
            'value' => '1',
            'isdefault' => 1,
            'disporder' =>$position++,
            'gid' => $gid,
        ),
        array(
            'name' => 'recaptcha_plugin_register_enabled',
            'title' => $db->escape_string($lang->recaptcha_plugin_register_enabled),
            'description' => $db->escape_string($lang->recaptcha_plugin_register_enabled_description),
            'optionscode' => 'yesno',
            'value' => '1',
            'isdefault' => 1,
            'disporder' =>$position++,
            'gid' => $gid,
        ),
    );
    foreach ($cfg as $settings) {
        $db->insert_query("settings", $settings);
    }
    rebuild_settings();
}

function recaptcha_plugin_activate() {
    global $db;
    $db->update_query("settings", array('value' => 1), "name='recaptcha_plugin_enabled'");
}

function recaptcha_plugin_deactivate() {
    global $db;
    $db->update_query("settings", array('value' => 0), "name='recaptcha_plugin_enabled'");
}

function recaptcha_plugin_is_installed() {
    global $mybb;
    return $mybb->settings['recaptcha_plugin_enabled'] !== null;
}

function recaptcha_plugin_uninstall() {
    global $db;

    $settingGroupId = $db->fetch_field(
            $db->simple_select('settinggroups', 'gid', "name='recaptcha_plugin'"), 'gid'
    );

    $db->delete_query('settinggroups', 'gid=' . (int) $settingGroupId);
    $db->delete_query('settings', 'gid=' . (int) $settingGroupId);

    rebuild_settings();
}
