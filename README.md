reCaptcha v2 plugin for MyBB
============================

This plugin provide a way to check if user posting new thread or reply is a human or not.

Instalation:
============

* Copy all folders and files from *inc* folder into MyBB *inc* directory.
* In your forum's Administrator Control Panel go to plugins section and install reCaptcha v2 plugin for MyBB
* Go to https://www.google.com/recaptcha/admin/create to create your site and secret keys for reCaptcha
* In your forum'S ACP config section (plugin settings) select "reCaptcha v2 plugin for MyBB", copy and paste your keys into designated fields
* In this section you also need to specify which use groups needs to be checked by reCaptcha.

After you save these settings, reCaptcha field should be visible by groups you're enabled for reCaptcha checks. If you need more information about reCaptcha, please read this FAQ: https://developers.google.com/recaptcha/docs/faq 

Requirements:
=============

* MyBB 1.8+
* curl - for HTTP requests to reCaptcha verification service

GitHub: https://github.com/ryczypior/recaptcha-plugin-for-mybb
