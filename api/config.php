<?php
/**
 * Database configuration
 * Johnny
 */
define('DB_USERNAME', 'seymour_booking');
define('DB_PASSWORD', 'SMB$ql1820');
define('DB_HOST', 'localhost');
define('DB_NAME', 'seymour_booking');

/* Site URL Config */
define('SITE_URL', 'http://booking.seymouraviation.com');

/* Brand ID */
define('SHORTNAME', 'Seymour Booking');
define('LONGNAME', 'Seymour Online Booking Application');

/*Site Email*/
define('FROM_EMAIL', 'booking@seymouraviation.com');
define('ADMIN_EMAIL', 'booking@seymouraviation.com');

/*Server SMTP Access*/
define('SMTP_SERVER', 'localhost');
define('SMTP_EMAIL','booking@seymouraviation.com');
define('SMTP_PWD','Smail1820');

/*Swiftmailer Auth Type - MAIL/SMTP*/
define('MAILER_TYPE','SMTP');

/*Hash Secret*/
define('FHS', 'rTxNwwoPaq14smONPKdl');

/*Login Token Lifetime*/
define('LOGIN_TOKEN_LIFETIME', '+24 hour');

/*Rave Params*/
define('RAVE_TEST_SECRET', 'FLWSECK-4009c89bb7681003fd2e5d97e6d83fcb-X'); //sandbox
define('RAVE_LIVE_SECRET', 'FLWSECK-808001052e147d70d8b376cbffb150c0-X'); //live

