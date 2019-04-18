<?php

/**
 * @author J. Schneider (j.schneider@mywave-solutions.nl)
 * @copyright 2017 MyWAVE Solutions
 * @version 2.0 
 * @return error Message (array, json or xml)
 * @comment xml not implemented yet.
 */
########################################################################## 
class ErrorHandling{

    private $_errorCode;
    private $_errorResult = array();
    private $_LOG = false;
    private $_MAIL = false;
    
    #---------------------------------------------------------------------
    public function __construct( KLogger $log, PHPMailer $PHPMailer )
    {    
        # Assign KLogger object to this class   
        $this->_LOG  = $log;
        $this->_MAIL = $PHPMailer;
    }
    
    #---------------------------------------------------------------------
    /**
    * @description  Retruns full error Message according to format
    * @param $format is return type. So far, json and PHP array only
    * @param $erroCode for retrieving full error notification
    */
    public function getError( $format=null, $errorCode, $errorText=null, $source=null ){
        
        # assign incoming errorCode to class
        $this->_errorCode = $errorCode;
        
        # Retrieve Full error message from Database
        $this->getErrorData();
        
        # Check if there was a customized result message send to this method
        if( $errorText != null )
        {    
            # If it's not null than replace standard retrieved errorText
            $this->_errorResult['text'] = $errorText;
        }
        
        # Check if ther was a customized source sent to this method
        if( $source != null )
        {
            # If it's not null than replace standard retrieved errorText
            $this->_errorResult['source'] = $source;
        }
        
        # Return Error Message based on format
        switch( $format ){
            case 'arr' : # Retruns PHP array
                return $this->_errorResult;
                break;
            case 'jsn' : # Returns json string
                return json_encode( $this->_errorResult );
                break;
            case 'mail' : # Email the error
                    $this->_emailError();
                break;
            default :
            
                # Drop a log line
                $this->_log->logInfo( "No format or wrong format for set for errorMessage" );
                
                # on Default ( wrong format ) PHP array output.
                return $errorMessage;
        }
    }
    
    #---------------------------------------------------------------------
    /**
     * @description Retrieve the data from the database
     * @param       $this->_errorCode
     * @return      $errorMessage, holding a full error data array
     */
    private function getErrorData(){
     
        # conn is a PDO object 
        $conn = new PDO( DB_INFO, DB_USER, DB_PASS ); 
        
        # Prepare statement right away;
        $stmt = $conn->prepare("
            SELECT code, title, text, source 
            FROM sys_error_codes 
            WHERE code = :code
        ");
        
        # execute the PDO statement
        $stmt->execute( array( ':code' => intval( $this->_errorCode ) ) );
    
        # Fetch results and assign to array
        $errorResult = $stmt->fetch( PDO::FETCH_ASSOC );
        
        # Check if there was a record fetched (to be on the save side)
        if( count ( $errorResult ) > 0 ){
            
            # Return this default errorCode
            $this->_errorResult = $errorResult; 
        }
        else 
        {
            # Drop a log line
            $this->_LOG->logInfo( "unkown Error code " . $this->_errorCode . " provided for errorMessage" );
                
            # Create default error Message
            $errorResult = array(
                'code'  => $this->_errorCode,
                'title' => "Unknown errorCode",
                'text'  => $this->_errorCode. " is not a valid errorCode."
            ); 
            
            # Return this default errorCode
            $this->_errorResult = $errorResult;         
        }
    }
    
    #---------------------------------------------------------------------
    /**
     *  @description Email the error (error is concidered critical)
     */
    private function _emailError()
    {
        # Set email variables
        $subject = $this->_errorResult['title'];
        $message = "Error code: " . $this->_errorResult['code'] ." | " . $this->_errorResult['text'];
        $message.= "\nError was generated at ".$this->_errorResult['source'];

        $this->_MAIL->isSMTP(); # Use SMTP

        # SMTP and authentication settings
        $this->_MAIL->SMTPDebug = 2;       # 2 = client and server messages
        $this->_MAIL->Host = 'smtp.gmail.com'; # Hostname Gsuite
        $this->_MAIL->Port = 587;          # Authenticated port
        $this->_MAIL->SMTPSecure = 'TLS';  # Authentication type
        $this->_MAIL->SMTPAuth = true;     # Authentication is set used

        # SMTP authentication credentials & Headers
        $this->_MAIL->Username = "tst.servicebox@gmail.com";
        $this->_MAIL->Password = "0nd3rHoud!";
        $this->_MAIL->setFrom('tst.servicebox@gmail.com', 'MRS Error Handler');
        $this->_MAIL->addReplyTo('admin@mywave-solutions.nl', 'MRS Auto Mailer');
        $this->_MAIL->addAddress('admin@mywave-solutions.nl', 'MRS System Administratot');

        $this->_MAIL->Subject = $subject; # Email subject line
        $this->_MAIL->Body = $message;    # Email Message (body content)

        # Send the email physically
        if (!$this->_MAIL->send()) {
            $this->_LOG->LogError("Error Handler Mailer Error: " . $this->_MAIL->ErrorInfo);
            return false;
        } else {
            $this->_LOG->LogError("Message with subject ". $subject. " has been sent.");
            return true;
        }
    }    
}
# END OF CLASS
?>