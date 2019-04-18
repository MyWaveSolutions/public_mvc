<?php

/**
 * @author Jan Schneider
 * @copyright MyWAVE Solutions 2017
 */
date_default_timezone_set('Europe/Amsterdam');

/**
 * @description Development and Test environment Demo
 */
define("DB_INFO", "mysql:host=localhost;dbname=database");
define("DB_USER", "username");
define("DB_PASS", "password");
$PDO = new PDO( DB_INFO, DB_USER, DB_PASS );

/**
 * @description PHPMailer is required by the ErrorHandling class, which all controllers use.
 */
include('../plugin/PHPMailer/OAuth.php');
include('../plugin/PHPMailer/PHPmailer.php');
include('../plugin/PHPMailer/POP3.php');
include('../plugin/PHPMailer/SMTP.php');
$PHPMailer = new PHPMailer();
$SMTP = new SMTP();

/**
 * @description PDF is requested in multiple controllers
 */
include('../plugin/fpdf/fpdf.php');
$PDF = new FPDF();


/**
 * @description Application Log File
 */
include("../config/KLogger.php");
define("LOG_FILE", "../logs/log_" . date("Y_m_d") . ".txt");
$LOG = new KLogger( LOG_FILE,  KLogger::INFO );

/**
 * @description Default Controller initialization
 */
include("../config/ErrorHandling.php");
$ERR = new ErrorHandling( $LOG, $PHPMailer );

/**
 * @description Application Root directory
 */
define("ROOT_DIR", "https://127.0.0.1/dev-onderhoud/");

/**
 * @description Email addresses for sending emails
 */
define("NEW_WORKNOTES_EMAIL_ADDRESS", "service@mywave-solutions.nl");
define("ERROR_HANDLER_EMAIL_ADDRESS", "admin@mywave-solutions.nl");
?>