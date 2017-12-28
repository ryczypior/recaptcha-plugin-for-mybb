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
if (!class_exists('RecaptchaPlugin')) {

    class RecaptchaPlugin {

        protected $enabled = true;
        protected $mybb = null;
        protected $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
        static public $last_error = null;

        protected function __construct($mybb, $lang) {
            $this->mybb = $mybb;
            if (!$this->mybb->settings['recaptcha_plugin_enabled']) {
                //throw new Exception('Plugin is not enabled');
                $this->enabled = false;
            }
            if (empty($this->mybb->settings['recaptcha_plugin_usergroups'])) {
                //throw new Exception('No usergroups are set for reCaptcha check');
                $this->enabled = false;
            }
            if (empty($this->mybb->settings['recaptcha_plugin_privatekey'])) {
                //throw new Exception('Secret key has not been set in plugin settings');
                $this->enabled = false;
            }
            if (empty($this->mybb->settings['recaptcha_plugin_publickey'])) {
                //throw new Exception('Site key has not been set in plugin settings');
                $this->enabled = false;
            }
            $is_member = is_member($this->mybb->settings['recaptcha_plugin_usergroups']);
            if (empty($is_member)) {
                //throw new Exception('User belongs to usergroup which is disabled for reCaptcha checks');
                $this->enabled = false;
            }
        }

        protected function reCaptchaV2Check($ip, $response) {
            $response = trim($response);
            $ret = false;
            if (!empty($response)) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $this->verify_url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, array(
                    'secret' => $this->mybb->settings['recaptcha_plugin_privatekey'],
                    'remoteip' => $ip,
                    'response' => $response,
                ));
                $result = curl_exec($ch);
                // Check for errors and display the error message
                if ($errno = curl_errno($ch)) {
                    $error_message = curl_strerror($errno);
                    self::$last_error = "cURL error ({$errno}):\n {$error_message}";
                } else {
                    try {
                        $json_result = json_decode($result, true);
                        if ($json_result['success']) {
                            $ret = true;
                        } else {
                            self::$last_error = $json_result['error-codes'];
                        }
                    } catch (\Exception $ex) {
                        self::$last_error = $ex->getMessage();
                    }
                }
                curl_close($ch);
            } else {
                self::$last_error = $lang->recaptcha_plugin_recaptcha_no_response_error;
            }
            return $ret;
        }

        protected function processTeplate($name, $params = null) {
            $ret = '';
            $file = __DIR__ . '/' . $name . '.template.php';
            if (is_file($file)) {
                $ret = file_get_contents($file);
                if (is_array($params)) {
                    foreach ($params as $k => $v) {
                        $ret = str_replace('{{'.$k.'}}', $v, $ret);
                    }
                }
            } else {
                self::$last_error = sprintf('Did not found a file %s', $file);
            }
            return $ret;
        }
        
        protected function processTeplateQuoted($name, $params = null) {
            $ret = $this->processTeplate($name, $params);
            return $this->quote($ret);
        }
        
        protected function quote($str){
            return addslashes($str);
        }

        static public function newThread($entry, $suffix = '') {
            global $mybb, $db, $lang, $template, $captcha, $altbg;
            $ret = '';
            try {
                $lang->load('recaptcha_plugin');
                $recaptcha = new self($mybb, $lang);
                if ($mybb->settings['recaptcha_plugin_threads_enabled'] && $recaptcha && $recaptcha->enabled) {
                    $lang->load('recaptcha_plugin');
                    if($altbg == 'trow1'){
                        $altbg = 'trow2';
                    } else {
                        $altbg = 'trow1';
                    }
                    eval ('$captcha .= "<tr><td class=\"$altbg\"><strong>'.$recaptcha->quote($lang->recaptcha_plugin_prove_human).'</strong></td><td class=\"$altbg\">' . $recaptcha->processTeplateQuoted('recaptcha.js') . $recaptcha->processTeplateQuoted('recaptcha.html', array('sitekey' => $mybb->settings['recaptcha_plugin_publickey'])) . '</td></tr>";');
                }
            } catch (Exception $ex) {
                
            }
            return $ret;
        }

        static public function newPost($entry) {
            global $mybb, $db, $lang, $template, $captcha, $altbg;
            try {
                $lang->load('recaptcha_plugin');
                $recaptcha = new self($mybb, $lang);
                if ($mybb->settings['recaptcha_plugin_posts_enabled'] && $recaptcha && $recaptcha->enabled) {
                    if($altbg == 'trow1'){
                        $altbg = 'trow2';
                    } else {
                        $altbg = 'trow1';
                    }
                    eval ('$captcha .= "<tr><td class=\"$altbg\"><strong>'.$recaptcha->quote($lang->recaptcha_plugin_prove_human).'</strong></td><td class=\"$altbg\">' . $recaptcha->processTeplateQuoted('recaptcha.js') . $recaptcha->processTeplateQuoted('recaptcha.html', array('sitekey' => $mybb->settings['recaptcha_plugin_publickey'])) . '</td></tr>";');
                }
            } catch (Exception $ex) {
                
            }
        }
        
        static public function quickReply($entry) {
            global $mybb, $db, $lang, $template, $quickreply;
            try {
                $lang->load('recaptcha_plugin');
                $recaptcha = new self($mybb, $lang);
                if ($mybb->settings['recaptcha_plugin_posts_enabled'] && $recaptcha && $recaptcha->enabled) {
                    if(!empty($quickreply)){
                        $tmp = '<tr><td class="trow2"><strong>'.$lang->recaptcha_plugin_prove_human.'</strong></td><td class="trow2">'.$recaptcha->processTeplate('recaptcha.js') . $recaptcha->processTeplate('recaptcha.html', array('sitekey' => $mybb->settings['recaptcha_plugin_publickey'])) . '</td></tr>';
                        $quickreply = preg_replace('/(<tbody.+?>\s*<tr>.+?<\/tr>)/is', '$1'.$tmp, $quickreply);
                    }
                }
            } catch (Exception $ex) {
                
            }
        }
        
        static public function newPostDone($entry = null) {
            global $mybb, $db, $lang;
            try {
                $lang->load('recaptcha_plugin');
                $recaptcha = new self($mybb, $lang);
                if ($mybb->settings['recaptcha_plugin_posts_enabled'] && $recaptcha && $recaptcha->enabled) {
                    $ret = $recaptcha->reCaptchaV2Check($_SERVER["REMOTE_ADDR"], $_POST["g-recaptcha-response"]);
                    if(!$ret){
                        if(!empty(self::$last_error)){
                            error(self::$last_error);
                        } else {
                            error($lang->recaptcha_plugin_recaptcha_default_error);
                        }
                    }
                }
            } catch (Exception $ex) {
                
            }
        }

    }

}