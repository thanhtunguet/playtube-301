<?php
if (file_exists('./assets/init.php')) {
    require_once('./assets/init.php');
} else {
    die('Please put this file in the home directory !');
}
if (!file_exists('update_langs')) {
    die('Folder ./update_langs is not uploaded and missing, please upload the update_langs folder.');
}

$versionToUpdate = '3.0';
$olderVersion = '2.2.8';
if ($pt->config->version == $versionToUpdate && $pt->config->filesVersion == $pt->config->version) {
    die("Your website is already updated to {$versionToUpdate}, nothing to do.");
}
if ($pt->config->version == $versionToUpdate && $pt->config->filesVersion != $pt->config->version) {
    die("Your website is database is updated to {$versionToUpdate}, but files are not uploaded, please upload all the files and make sure to use SFTP, all files should be overwritten.");
}
if ($pt->config->version < $olderVersion) {
    die("Please update to {$olderVersion} first version by version, your current version is: " . $pt->config->version);
}

$updated = false;
if (!empty($_GET['updated'])) {
    $updated = true;
}
if (!empty($_POST['query'])) {
    $query = mysqli_query($mysqli, base64_decode($_POST['query']));
    if ($query) {
        $data['status'] = 200;
    } else {
        $data['status'] = 400;
        $data['error']  = mysqli_error($mysqli);
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    return rmdir($dir);
}
function updateLangs($lang) {
    global $sqlConnect;
    if (!file_exists("update_langs/{$lang}.txt")) {
        $filename = "update_langs/unknown.txt";
    } else {
        $filename = "update_langs/{$lang}.txt";
    }
    // Temporary variable, used to store current query
    $templine = '';
    // Read in entire file
    $lines    = file($filename);
    // Loop through each line
    foreach ($lines as $line) {
        // Skip it if it's a comment
        if (substr($line, 0, 2) == '--' || $line == '')
            continue;
        // Add this line to the current segment
        $templine .= $line;
        $query = false;
        // If it has a semicolon at the end, it's the end of the query
        if (substr(trim($line), -1, 1) == ';') {
            // Perform the query
            $templine = str_replace('`{unknown}`', "`{$lang}`", $templine);
            //echo $templine;
            $query    = mysqli_query($sqlConnect, $templine);
            // Reset temp variable to empty
            $templine = '';
        }
    }
}
if (!empty($_POST['update_langs'])) {
    $data  = array();
    $query = mysqli_query($sqlConnect, "SHOW COLUMNS FROM `langs`");
    while ($fetched_data = mysqli_fetch_assoc($query)) {
        $data[] = $fetched_data['Field'];
    }
    unset($data[0]);
    unset($data[1]);
    unset($data[2]);
    $lang_update_queries = array();
    foreach ($data as $key => $value) {
        updateLangs($value);
    }
    $deleteFile = deleteDirectory("update_langs");
    $db->where('name', 'version')->update(T_CONFIG, ['value' => $versionToUpdate]);
    $name = md5(microtime()) . '_updated.php';
    rename('update.php', $name);
}
?>
<html>
   <head>
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
      <meta name="viewport" content="width=device-width, initial-scale=1"/>
      <title>Updating PlayTube</title>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
      <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
      <style>
         @import url('https://fonts.googleapis.com/css?family=Roboto:400,500');
         @media print {
            .wo_update_changelog {max-height: none !important; min-height: !important}
            .btn, .hide_print, .setting-well h4 {display:none;}
         }
         * {outline: none !important;}
         body {background: #f3f3f3;font-family: 'Roboto', sans-serif;}
         .light {font-weight: 400;}
         .bold {font-weight: 500;}
         .btn {height: 52px;line-height: 1;font-size: 16px;transition: all 0.3s;border-radius: 2em;font-weight: 500;padding: 0 28px;letter-spacing: .5px;}
         .btn svg {margin-left: 10px;margin-top: -2px;transition: all 0.3s;vertical-align: middle;}
         .btn:hover svg {-webkit-transform: translateX(3px);-moz-transform: translateX(3px);-ms-transform: translateX(3px);-o-transform: translateX(3px);transform: translateX(3px);}
         .btn-main {color: #ffffff;background-color: #00BCD4;border-color: #00BCD4;}
         .btn-main:disabled, .btn-main:focus {color: #fff;}
         .btn-main:hover {color: #ffffff;background-color: #0dcde2;border-color: #0dcde2;box-shadow: -2px 2px 14px rgba(168, 72, 73, 0.35);}
         svg {vertical-align: middle;}
         .main {color: #00BCD4;}
         .wo_update_changelog {
          border: 1px solid #eee;
          padding: 10px !important;
         }
         .content-container {display: -webkit-box; width: 100%;display: -moz-box;display: -ms-flexbox;display: -webkit-flex;display: flex;-webkit-flex-direction: column;flex-direction: column;min-height: 100vh;position: relative;}
         .content-container:before, .content-container:after {-webkit-box-flex: 1;box-flex: 1;-webkit-flex-grow: 1;flex-grow: 1;content: '';display: block;height: 50px;}
         .wo_install_wiz {position: relative;background-color: white;box-shadow: 0 1px 15px 2px rgba(0, 0, 0, 0.1);border-radius: 10px;padding: 20px 30px;border-top: 1px solid rgba(0, 0, 0, 0.04);}
         .wo_install_wiz h2 {margin-top: 10px;margin-bottom: 30px;display: flex;align-items: center;}
         .wo_install_wiz h2 span {margin-left: auto;font-size: 15px;}
         .wo_update_changelog {padding:0;list-style-type: none;margin-bottom: 15px;max-height: 440px;overflow-y: auto; min-height: 440px;}
         .wo_update_changelog li {margin-bottom:7px; max-height: 20px; overflow: hidden;}
         .wo_update_changelog li span {padding: 2px 7px;font-size: 12px;margin-right: 4px;border-radius: 2px;}
         .wo_update_changelog li span.added {background-color: #4CAF50;color: white;}
         .wo_update_changelog li span.changed {background-color: #e62117;color: white;}
         .wo_update_changelog li span.improved {background-color: #9C27B0;color: white;}
         .wo_update_changelog li span.compressed {background-color: #795548;color: white;}
         .wo_update_changelog li span.fixed {background-color: #2196F3;color: white;}
         input.form-control {background-color: #f4f4f4;border: 0;border-radius: 2em;height: 40px;padding: 3px 14px;color: #383838;transition: all 0.2s;}
input.form-control:hover {background-color: #e9e9e9;}
input.form-control:focus {background: #fff;box-shadow: 0 0 0 1.5px #a84849;}
         .empty_state {margin-top: 80px;margin-bottom: 80px;font-weight: 500;color: #6d6d6d;display: block;text-align: center;}
         .checkmark__circle {stroke-dasharray: 166;stroke-dashoffset: 166;stroke-width: 2;stroke-miterlimit: 10;stroke: #7ac142;fill: none;animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;}
         .checkmark {width: 80px;height: 80px; border-radius: 50%;display: block;stroke-width: 3;stroke: #fff;stroke-miterlimit: 10;margin: 100px auto 50px;box-shadow: inset 0px 0px 0px #7ac142;animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;}
         .checkmark__check {transform-origin: 50% 50%;stroke-dasharray: 48;stroke-dashoffset: 48;animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;}
         @keyframes stroke { 100% {stroke-dashoffset: 0;}}
         @keyframes scale {0%, 100% {transform: none;}  50% {transform: scale3d(1.1, 1.1, 1); }}
         @keyframes fill { 100% {box-shadow: inset 0px 0px 0px 54px #7ac142; }}
      </style>
   </head>
   <body>
      <div class="content-container container">
         <div class="row">
            <div class="col-md-1"></div>
            <div class="col-md-10">
               <div class="wo_install_wiz">
                 <?php if ($updated == false) { ?>
                  <div>
                     <h2 class="light">Update to v<?php echo $versionToUpdate?> </span></h2>
                     <div class="alert alert-danger">
                       <strong>Important:</strong> Don't run the update process before all the files were uploaded to your server, please make sure all files are uploaded to your server then click the update button below.
                     </div>
                     <div class="setting-well">
                        <h4>Changelog</h4>
                        <ul class="wo_update_changelog">
                        <li>[Added] the ability to embed videos from the import system [enable/disable].</li>
                            <li>[Added] Google Auth + Authy system for two authentication [enable/disable].</li>
                            <li>[Added] the ability to import M3U8 URL [enable/disable].</li>
                            <li>[Added] a new updated design for the "default" theme.</li>
                            <li>[Added] the ability to upload video to subscribers only.</li>
                            <li>[Added] privacy system, a channel owner can set their privacy for many options in the website.</li>
                            <li>[Added] auto shorts import from YouTube, same as normal YouTube auto importer.</li>
                            <li>[Added] yandex cloud storage.</li>
                            <li>[Added] import from YouTube shorts for users [enable/disable].</li>
                            <li>[Added] google cloud storage.</li>
                            <li>[Added] the ability to get notifications from channels when uploading a new video.</li>
                            <li>[Added] advanced affiliate system.</li>
                            <li>[Added] withdrawal methods.</li>
                            <li>[Added] video series system [enable/disable].</li>
                            <li>[Added] Flutterwave payment system.</li>
                            <li>[Added] developer apps, users can use your website's API now.</li>
                            <li>[Added] hashtag support, users can write and search by hashtags.</li>
                            <li>[Added] the ability to import a video as a movie.</li>
                            <li>[Added] qiwi payment system.</li>
                            <li>[Added] login with TikTok.</li>
                            <li>[Fixed] google login.</li>
                            <li>[Fixed] after importing a tiktok video, on homepage it shows time 00:00</li>
                            <li>[Fixed] when user decide to unsubscribe - the buttons yes / no are empty </li>
                            <li>[Fixed] if a user you're subscribed to uploads a video, you don't get a notification</li>
                            <li>[Fixed] Key PayPal E-mail was not translatable in balance page.</li>
                            <li>[Fixed] admin-cp/monetization-requests clicking verify or deny the row doesn't do anything.</li>
                            <li>[Fixed] after purchasing a pro package, I go to go_pro link again when payment done, which cause to go to 404 page, he should go to payment successful page instead.</li>
                            <li>[Fixed] can't scroll in auto import page after loading another 50 videos.</li>
                            <li>[Fixed] delete user not working.</li>
                            <li>[Fixed] webp images were not working.</li>
                        </ul>
                        <p class="hide_print">Note: The update process might take few minutes.</p>
                        <p class="hide_print">Important: If you got any fail queries, please copy them, open a support ticket and send us the details.</p>
                        <br>
                             <button class="pull-right btn btn-default" onclick="window.print();">Share Log</button>
                             <button type="button" class="btn btn-main" id="button-update">
                             Update
                             <svg viewBox="0 0 19 14" xmlns="http://www.w3.org/2000/svg" width="18" height="18">
                                <path fill="currentColor" d="M18.6 6.9v-.5l-6-6c-.3-.3-.9-.3-1.2 0-.3.3-.3.9 0 1.2l5 5H1c-.5 0-.9.4-.9.9s.4.8.9.8h14.4l-4 4.1c-.3.3-.3.9 0 1.2.2.2.4.2.6.2.2 0 .4-.1.6-.2l5.2-5.2h.2c.5 0 .8-.4.8-.8 0-.3 0-.5-.2-.7z"></path>
                             </svg>
                          </button>
                     </div>
                     <?php }?>
                     <?php if ($updated == true) { ?>
                      <div>
                        <div class="empty_state">
                           <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                              <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                              <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                           </svg>
                           <p>Congratulations, you have successfully updated your site. Thanks for choosing PlayTube.</p>
                           <br>
                           <a href="<?php echo $wo['config']['site_url'] ?>" class="btn btn-main" style="line-height:50px;">Home</a>
                        </div>
                     </div>
                     <?php }?>
                  </div>
               </div>
            </div>
            <div class="col-md-1"></div>
         </div>
      </div>
   </body>
</html>
<script>
var queries = [
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'affiliate_system', '0');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'affiliate_type', '0');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'amount_ref', '0.10');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'amount_percent_ref', '0');",
    "ALTER TABLE `users` ADD `referrer` INT(11) NOT NULL DEFAULT '0' AFTER `permission`, ADD INDEX (`referrer`);",
    "ALTER TABLE `users` ADD `ref_user_id` INT(11) NOT NULL DEFAULT '0' AFTER `referrer`, ADD INDEX (`ref_user_id`);",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'google_vignette', '');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'fluttewave_payment', 'no');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'fluttewave_secret_key', '');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'tiktok_login', 'off');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'tiktok_client_key', '');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'tiktok_client_secret', '');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'youtube_short', 'off');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'embed_videos', 'off');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'review_embed_videos', 'off');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'developers_page', 'on');",
    "ALTER TABLE `videos` ADD `embed` INT(2) NOT NULL DEFAULT '0' AFTER `twitch_type`, ADD INDEX (`embed`);",
    "CREATE TABLE `apps` (  `id` int(11) NOT NULL AUTO_INCREMENT,  `app_user_id` int(11) NOT NULL DEFAULT 0,  `app_name` varchar(32) NOT NULL,  `app_website_url` varchar(55) NOT NULL,  `app_description` text NOT NULL,  `app_avatar` varchar(100) NOT NULL DEFAULT 'upload/photos/app-default-icon.png',  `app_callback_url` varchar(255) NOT NULL,  `app_id` varchar(32) NOT NULL,  `app_secret` varchar(55) NOT NULL,  `active` enum('0','1') NOT NULL DEFAULT '1',  PRIMARY KEY (`id`),  KEY `app_user_id` (`app_user_id`),  KEY `app_id` (`app_id`),  KEY `active` (`active`)) ENGINE=InnoDB ;",
    "CREATE TABLE `apps_permission` (  `id` int(11) NOT NULL AUTO_INCREMENT,  `user_id` int(11) NOT NULL,  `app_id` int(11) NOT NULL,  PRIMARY KEY (`id`),  KEY `user_id` (`user_id`,`app_id`)) ENGINE=InnoDB ;",
    "CREATE TABLE `apps_codes` (  `id` int(11) NOT NULL AUTO_INCREMENT,  `code` varchar(50) NOT NULL DEFAULT '',  `app_id` varchar(50) NOT NULL DEFAULT '',  `user_id` int(11) NOT NULL DEFAULT 0,  `time` int(11) NOT NULL DEFAULT 0,  PRIMARY KEY (`id`),  KEY `code` (`code`),  KEY `user_id` (`user_id`),  KEY `app_id` (`app_id`)) ENGINE=InnoDB ;",
    "CREATE TABLE `hashtags` ( `id` INT(11) NOT NULL AUTO_INCREMENT , `tag` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' , `time` INT(11) NOT NULL DEFAULT '0' , PRIMARY KEY (`id`), INDEX (`tag`), INDEX (`time`)) ENGINE = InnoDB;",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'hashtag_system', 'on');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'contact_us_email', '');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'alipay_payment', 'no');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'alipay_client_id', '');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'alipay_public_key', '');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'alipay_private_key', '');",
    "UPDATE `config` SET `value` = '{\"affiliate_new_user\":1,\"affiliate_pro\":1,\"affiliate_subscribe\":1,\"affiliate_buy_rent\":1}' WHERE `config`.`name` = 'affiliate_type';",
    "ALTER TABLE `users` ADD `ref_type` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `ref_user_id`;",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'amount_percent_subscribe', '10');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'amount_percent_buy_rent', '10');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'cloud_upload', 'off');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'cloud_bucket_name', '');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'cloud_file_path', '');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'cloud_endpoint', '');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'withdrawal_payment_method', '{\"paypal\":1,\"bank\":0,\"skrill\":0,\"custom\":0}');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'custom_name', '');",
    "ALTER TABLE `withdrawal_requests` ADD `iban` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `currency`, ADD `country` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `iban`, ADD `full_name` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `country`, ADD `swift_code` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `full_name`, ADD `address` VARCHAR(600) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `swift_code`, ADD `transfer_info` VARCHAR(600) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `address`;",
    "ALTER TABLE `withdrawal_requests` ADD `type` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `transfer_info`;",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'yandex_storage', 'off');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'yandex_name', '');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'yandex_key', '');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'yandex_secret', '');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'yandex_region', 'ru-central1-a');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'google_authenticator', 'off');",
    "ALTER TABLE `users` ADD `two_factor_method` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `two_factor`;",
    "ALTER TABLE `users` ADD `google_secret` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `two_factor`;",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'authy_settings', 'off');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'authy_token', '');",
    "ALTER TABLE `users` ADD `authy_id` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `google_secret`;",
    "ALTER TABLE `users` ADD `privacy` VARCHAR(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '{\"show_subscriptions_count\":\"yes\",\"who_can_message_me\":\"all\",\"who_can_watch_my_videos\":\"all\"}' AFTER `ref_type`;",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'instagram_import', 'off');",
    "ALTER TABLE `videos` ADD `instagram` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `facebook`;",
    "CREATE TABLE `manage_pro` (  `id` int(11) NOT NULL AUTO_INCREMENT,  `type` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '',  `price` float NOT NULL DEFAULT 0,  `featured_videos` int(2) NOT NULL DEFAULT 0,  `verified_badge` int(2) NOT NULL DEFAULT 0,  `discount` int(11) NOT NULL DEFAULT 0,  `image` varchar(300) CHARACTER SET utf8 NOT NULL DEFAULT '',  `night_image` varchar(300) CHARACTER SET utf8 NOT NULL DEFAULT '',  `color` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '#fafafa',  `description` varchar(1000) CHARACTER SET utf8 NOT NULL DEFAULT '',  `status` int(2) NOT NULL DEFAULT 1,  `time` varchar(20) CHARACTER SET utf8 NOT NULL DEFAULT 'month',  `time_count` int(11) NOT NULL DEFAULT 1,  `max_upload` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '96000000',  `features` varchar(1000) CHARACTER SET utf8 NOT NULL DEFAULT '',  PRIMARY KEY (`id`),  KEY `type` (`type`),  KEY `price` (`price`),  KEY `featured_videos` (`featured_videos`),  KEY `verified_badge` (`verified_badge`),  KEY `discount` (`discount`),  KEY `image` (`image`),  KEY `night_image` (`night_image`),  KEY `color` (`color`),  KEY `status` (`status`),  KEY `time` (`time`),  KEY `time_count` (`time_count`),  KEY `max_upload` (`max_upload`),  KEY `features` (`features`)) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_article', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_playlist', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_post', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_payed_subscribers', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_donate', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_invite_links', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_point', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_upload', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_import', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_youtube_short', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_ok_import', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_facebook_import', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_twitch_import', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_instagram_import', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_tiktok_import', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_embed_videos', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_trailer_system', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_restrict_embedding', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_video_text', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_stock_videos', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_download_videos', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_movies_videos', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_geo_blocking', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_shorts', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_hashtag', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_sell_videos', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_demo_video', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_rent_videos', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_live_video', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_live_save', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_user_ads', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_usr_v_mon', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_affiliate', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_affiliate_new_user', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_affiliate_pro', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_affiliate_subscribe', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_affiliate_buy_rent', 'all');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'who_can_pro_google', 'all');",
    "ALTER TABLE `manage_pro` CHANGE `features` `features` VARCHAR(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '{\"can_use_article\":1,\"can_use_playlist\":1,\"can_use_post\":1,\"can_use_payed_subscribers\":1,\"can_use_donate\":1,\"can_use_invite_links\":1,\"can_use_point\":1,\"can_use_upload\":1,\"can_use_import\":1,\"can_use_youtube_short\":1,\"can_use_ok_import\":1,\"can_use_facebook_import\":1,\"can_use_instagram_import\":1,\"can_use_twitch_import\":1,\"can_use_tiktok_import\":1,\"can_use_embed_videos\":1,\"can_use_trailer_system\":1,\"can_use_restrict_embedding\":1,\"can_use_video_text\":1,\"can_use_stock_videos\":1,\"can_use_download_videos\":1,\"can_use_movies_videos\":1,\"can_use_geo_blocking\":1,\"can_use_shorts\":1,\"can_use_hashtag\":1,\"can_use_sell_videos\":1,\"can_use_rent_videos\":1,\"can_use_live_video\":1,\"can_use_live_save\":1,\"can_use_user_ads\":1,\"can_use_usr_v_mon\":1,\"can_use_affiliate\":1,\"can_use_affiliate_new_user\":1,\"can_use_affiliate_pro\":1,\"can_use_affiliate_subscribe\":1,\"can_use_affiliate_buy_rent\":1,\"can_use_pro_google\":1}';",
    "ALTER TABLE `users` ADD `pro_type` INT(2) NOT NULL DEFAULT '0' AFTER `is_pro`, ADD INDEX (`pro_type`);",
    "INSERT INTO `manage_pro` (`id`, `type`, `price`, `featured_videos`, `verified_badge`, `discount`, `image`, `night_image`, `color`, `description`, `status`, `time`, `time_count`, `max_upload`, `features`) VALUES (NULL, 'Pro', '10', '1', '1', '0', '', '', '#2216C5', '', '1', 'month', '1', '96000000', '{\"can_use_article\":1,\"can_use_playlist\":1,\"can_use_post\":1,\"can_use_payed_subscribers\":1,\"can_use_donate\":1,\"can_use_invite_links\":1,\"can_use_point\":1,\"can_use_upload\":1,\"can_use_import\":1,\"can_use_youtube_short\":1,\"can_use_ok_import\":1,\"can_use_facebook_import\":1,\"can_use_instagram_import\":1,\"can_use_twitch_import\":1,\"can_use_tiktok_import\":1,\"can_use_embed_videos\":1,\"can_use_trailer_system\":1,\"can_use_restrict_embedding\":1,\"can_use_video_text\":1,\"can_use_stock_videos\":1,\"can_use_download_videos\":1,\"can_use_movies_videos\":1,\"can_use_geo_blocking\":1,\"can_use_shorts\":1,\"can_use_hashtag\":1,\"can_use_sell_videos\":1,\"can_use_rent_videos\":1,\"can_use_live_video\":1,\"can_use_live_save\":1,\"can_use_user_ads\":1,\"can_use_usr_v_mon\":1,\"can_use_affiliate\":1,\"can_use_affiliate_new_user\":1,\"can_use_affiliate_pro\":1,\"can_use_affiliate_subscribe\":1,\"can_use_affiliate_buy_rent\":1,\"can_use_pro_google\":1}');",
    "ALTER TABLE `subscriptions` CHANGE `notify` `notify` INT(11) NOT NULL DEFAULT '0';",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'fb_api_id', '');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'fb_api_sc', '');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'point_system_admob_cost', '10');",
    "INSERT INTO `config` (`id`, `name`, `value`) VALUES (NULL, 'lookup_key', '');",
    "ALTER TABLE `videos` ADD INDEX(`category_id`, `id`);",
    "CREATE TABLE `dashboard_reports` ( `id` INT(11) NOT NULL AUTO_INCREMENT , `name` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' , `value` VARCHAR(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' , PRIMARY KEY (`id`), INDEX (`name`)) ENGINE = InnoDB;",
    "CREATE TABLE `backup_codes` ( `id` INT(11) NOT NULL AUTO_INCREMENT , `user_id` INT(11) NOT NULL DEFAULT '0' , `codes` VARCHAR(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' , PRIMARY KEY (`id`), INDEX (`user_id`)) ENGINE = InnoDB;",
    "ALTER TABLE `videos` ADD `featured_movie` INT(2) NOT NULL DEFAULT '0' AFTER `is_short`, ADD INDEX (`featured_movie`);",
    "INSERT INTO `dashboard_reports` (`id`, `name`, `value`) VALUES(1, 'This Year', '{\"users_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"posts_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"videos_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"comments_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"likes_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"dislikes_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\"}'),(2, 'Today', '{\"users_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"posts_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"videos_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"comments_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"likes_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"dislikes_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\"}'),(3, 'Yesterday', '{\"users_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"posts_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"videos_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"comments_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"likes_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"dislikes_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\"}'),(4, 'This Week', '{\"users_array\":\"0, 0, 0, 0, 0, 0, 0\",\"posts_array\":\"0, 0, 0, 0, 0, 0, 0\",\"videos_array\":\"0, 0, 0, 0, 0, 0, 0\",\"comments_array\":\"0, 0, 0, 0, 0, 0, 0\",\"likes_array\":\"0, 0, 0, 0, 0, 0, 0\",\"dislikes_array\":\"0, 0, 0, 0, 0, 0, 0\"}'),(5, 'This Month', '{\"users_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"posts_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"videos_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"comments_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"likes_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"dislikes_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\"}'),(6, 'Last Month', '{\"users_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"posts_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"videos_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"comments_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"likes_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\",\"dislikes_array\":\"0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0\"}');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'paypal_email');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'now_pro');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'upgraded');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'my_affiliates');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'earn_users');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'earn_users_pro');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'your_ref_link');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'share_to');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'joined');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'your_balance_shows');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'your_wallet_shows');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'your_money_shows');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'flutterwave');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_embed_website');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'developers');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'my_apps');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'create_app');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'no_apps_found');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'domain');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'redirect_uri');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'publish');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'invalid_url');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'app_created_successfully');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'app');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'edit_app');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'app_not_found');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'app_edited_successfully');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'permission');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'app_id');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'app_secret');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'app_permission');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'back');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'accept');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'receive_the_following_info');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'app_not_found');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'app_permission_accepted');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'user_data');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'alipay');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'youtube_short');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'enable_notify');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'disable_notify');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'earn_money_when_new_user');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'earn_money_when_new_user_pro');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'earn_money_when_new_user_subscribe');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'earn_money_when_new_user_purchase');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'import_as');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'affiliates');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'join_now');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'requires_subscription');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'earn_users_subscribe');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'earn_users_buy_rent');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'withdraw_method');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'transfer_to');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'skrill');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'bank');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'iban');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'full_name');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'swift_code');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'please_select_payment_method');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'two_factor_method');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'google_authenticator');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'send');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'empty_code');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'select_two_factor_method');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'authy_app');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'authy_register');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'empty_email');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'empty_phone');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'invalid_phone');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'country_code');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'empty_country_code');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'authy_registered');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'use_authy_app');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'use_google_authenticator_app');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'show_subscriptions_count');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'who_can_message_me');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'no_one');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'who_can_watch_my_videos');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'only_me');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'please_subscribe_to_watch');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_article');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_playlist');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_post');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_payed_subscribers');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_donate');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_invite_links');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_point');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_upload');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_import');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_youtube_short');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_ok_import');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_facebook_import');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_instagram_import');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_twitch_import');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_tiktok_import');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_embed_videos');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_trailer_system');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_restrict_embedding');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_video_text');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_stock_videos');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_download_videos');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_movies_videos');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_geo_blocking');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_shorts');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_hashtag');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_sell_videos');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_rent_videos');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_live_video');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_live_save');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_user_ads');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_usr_v_mon');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_affiliate');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_affiliate_new_user');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_affiliate_pro');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_affiliate_subscribe');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_affiliate_buy_rent');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'can_use_pro_google');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'discount');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'something_wrong_send_messages');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'deactivate');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'two_auth_currenly_enabled');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'authenticator_download');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'authenticator_set');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'authenticator_verify');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'authenticator_otp');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'download_backup_codes');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'watch');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'update_your_statics');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'information_in_setting');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'welcome_backk');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'dont_have_account');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'create_new_account');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'explore_pro_feat');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'explore_pro_desc');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'start_for_free');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'already_member');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'gender_label');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'back_to_home');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'more_link');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'video_history_text');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'country_label');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'pro_package_short_text');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'pro_billed_monthly');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'featured_include_pro');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'live_ettings');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'how_playtube_works');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'play_all');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'profile_settings');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'profile_static');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'profile_descp_static');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'cancel_comment');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'comment_text');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'sub_text');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'dislike_text');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'side_days_text');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'upload_file_media');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'happy_point');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'video_type');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'time_video');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'age_text_video');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'view_all_notifs');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'mark_as_read_text');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'user_video_url_import');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'your_private_video');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'channel_of_this_month');",
];

$('#input_code').bind("paste keyup input propertychange", function(e) {
    if (isPurchaseCode($(this).val())) {
        $('#button-update').removeAttr('disabled');
    } else {
        $('#button-update').attr('disabled', 'true');
    }
});

function isPurchaseCode(str) {
    var patt = new RegExp("(.*)-(.*)-(.*)-(.*)-(.*)");
    var res = patt.test(str);
    if (res) {
        return true;
    }
    return false;
}

$(document).on('click', '#button-update', function(event) {
    if ($('body').attr('data-update') == 'true') {
        window.location.href = '<?php echo $site_url?>';
        return false;
    }
    $(this).attr('disabled', true);
    $('.wo_update_changelog').html('');
    $('.wo_update_changelog').css({
        background: '#1e2321',
        color: '#fff'
    });
    $('.setting-well h4').text('Updating..');
    $(this).attr('disabled', true);
    RunQuery();
});

var queriesLength = queries.length;
var query = queries[0];
var count = 0;
function b64EncodeUnicode(str) {
    // first we use encodeURIComponent to get percent-encoded UTF-8,
    // then we convert the percent encodings into raw bytes which
    // can be fed into btoa.
    return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g,
        function toSolidBytes(match, p1) {
            return String.fromCharCode('0x' + p1);
    }));
}
function RunQuery() {
    var query = queries[count];
    $.post('?update', {
        query: b64EncodeUnicode(query)
    }, function(data, textStatus, xhr) {
        if (data.status == 200) {
            $('.wo_update_changelog').append('<li><span class="added">SUCCESS</span> ~$ mysql > ' + query + '</li>');
        } else {
            $('.wo_update_changelog').append('<li><span class="changed">FAILED</span> ~$ mysql > ' + query + '</li>');
        }
        count = count + 1;
        if (queriesLength > count) {
            setTimeout(function() {
                RunQuery();
            }, 1500);
        } else {
            $('.wo_update_changelog').append('<li><span class="added">Updating Langauges & Categories</span> ~$ languages.sh, Please wait, this might take some time..</li>');
            $.post('?run_lang', {
                update_langs: 'true'
            }, function(data, textStatus, xhr) {
              $('.wo_update_changelog').append('<li><span class="fixed">Finished!</span> ~$ Congratulations! you have successfully updated your site. Thanks for choosing PlayTube.</li>');
              $('.setting-well h4').text('Update Log');
              $('#button-update').html('Home <svg viewBox="0 0 19 14" xmlns="http://www.w3.org/2000/svg" width="18" height="18"> <path fill="currentColor" d="M18.6 6.9v-.5l-6-6c-.3-.3-.9-.3-1.2 0-.3.3-.3.9 0 1.2l5 5H1c-.5 0-.9.4-.9.9s.4.8.9.8h14.4l-4 4.1c-.3.3-.3.9 0 1.2.2.2.4.2.6.2.2 0 .4-.1.6-.2l5.2-5.2h.2c.5 0 .8-.4.8-.8 0-.3 0-.5-.2-.7z"></path> </svg>');
              $('#button-update').attr('disabled', false);
              $(".wo_update_changelog").scrollTop($(".wo_update_changelog")[0].scrollHeight);
              $('body').attr('data-update', 'true');
            });
        }
        $(".wo_update_changelog").scrollTop($(".wo_update_changelog")[0].scrollHeight);
    });
}
</script>
