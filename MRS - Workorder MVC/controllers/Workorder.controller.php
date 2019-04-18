<?php

/**
 * @author Jan Schneider
 * @copyright 2016
 */
 
session_start();

include('../config/config.php');
include('../lib/EmailNotifications.class.php');
include('../lib/EngineerApp.class.php');
include('../lib/Maintenance.class.php');
include('../lib/Planboard.class.php');
include('../lib/Priority.class.php');
include('../lib/Status.class.php');
include('../lib/Ticket.class.php');
include('../lib/User.class.php');
include('../lib/UserSettings.class.php');
include('../lib/ServiceRapport.class.php');
include('../lib/Worknote.class.php');
include('../lib/Workorder.class.php');
include('../lib/WorkorderData.class.php');

if( isset( $_POST['directPost'] ) && $_POST['directPost'] ){
    
    $directPost = explode( "," , $_POST['directPost'] );
    $function = $directPost[0];
    
    $WORKORDER = new Workorder( $ERR, $LOG );
    $TICKET = new Ticket( $ERR, $LOG );
    $EMAIL = new EmailNotification( $ERR, $LOG, $PHPMailer );
    
    switch( $function )
    {
        case 'getWorkorderData' :
            $workorderID = $directPost[1];
            
            # Check the user rights
            $USER_SETTINGS = new UserSettings( $ERR, $LOG ); 
            $userSettings = $USER_SETTINGS->getSettingsById( $_SESSION['USER_ID'] );
            
            # Get all the data we need.
            $WorkorderData = $WORKORDER->getById( $workorderID );
            $workorderAttachments = $WORKORDER->getAttachments( $workorderID );
            
            # Check wether the ordertype is "Ticket" (hour-based incident)
            if( intval( $WorkorderData['workorder_type_id'] ) == 2 )
            {  
                $ticketData = $TICKET->getById( $WorkorderData['order_id'] );
                $ticketImages = $TICKET->getImages( $WorkorderData['order_id'] );   
                                
                # Combine the two as one multi-dimensional array
                $returnArray = array(
                    'orderType'    => 2,
                    'workorder'    => $WorkorderData,
                    'attachments'  => $workorderAttachments, 
                    'ticket'       => $ticketData,
                    'ticketImages' => $ticketImages,
                    'userSettings' => $userSettings
                ); 
            } 
            else {
                $maintenanceInfo = $WORKORDER->getById( $workorderID ); 
                
                # Combine the two as one multi-dimensional array
                $returnArray = array(
                    'orderType'    => 1,
                    'workorder'    => $WorkorderData,
                    'attachments'  => $workorderAttachments, 
                    'maintenance'  => $maintenanceInfo,
                    'userSettings' => $userSettings
                );   
            }
            
            # In this case, the directPost[1] is the ticket ID
            print( json_encode( $returnArray ) );
            
        break;
    #--------------------------------------------
        case 'assignConstructor' :
            
            # Instantiate the ticket_data class and run invoice caving method
            $WORKORDER_DATA = new WorkorderData( $directPost[2]);
            $constractor = $WORKORDER_DATA->assignConstructor( $directPost[1] );
           
            if( $constractor !== false )
            {
                # Set message that invoice number has been attached
                $constrMsg = "Aannemer " . $constractor ." is gekoppeld aan deze storings ticket.";
                
                # Assign the invoicenumber to the wokrnotes
                $WORKORDER_DATA->saveWorknote($constrMsg, 2);
                
                # Parse saving result back to browser.
                print( json_encode( true ) );
            } else {
                print( json_encode( false ) );
            }

        break;
    #--------------------------------------------
        case 'getOpenWorkorders' :  
            $returnArray = $WORKORDER->getOpenWorkorders();
            print( json_encode( $returnArray ) ); 
        break;
    #--------------------------------------------
        case 'runStatusOrder' :
            $order = $directPost[1];
            $workorders = $WORKORDER->getOpenWorkordersInOrderOfStatus( $order );
            print( json_encode( $workorders ) );
        break;
    #--------------------------------------------
        case 'getWorkorderByStatus' :
            $statusId = $directPost[1];
            $workorders = $WORKORDER->getByStatusId( $statusId );
            print( json_encode( $workorders ) );
        break;
    #--------------------------------------------
        case 'getPlannedWorkorders' :
            $workorders = $WORKORDER->getPlannedWorkorders();

            if( is_array( $workorders ) )
            {
                foreach( $workorders as $key => $value )
                {
                    # The new value must be written back to the Foreach.
                    $PRIOR = new Priority( $ERR, $LOG );
                    $range = $PRIOR->getPriorityRangeOfWorkorder( $value['start_date'], $value['busId'] );
                    $value['range'] = $range;
                    $newWorkorders[$key] = $value;
                }
                print( json_encode( $newWorkorders ) );
            }
            else { print( "false" ); }
        break;
    #---------------------------------------------------------------------
        case 'getArchivedWorkorders' :
        
            # Get all the arhcived tickets
            print( json_encode( $WORKORDER->getArchived() ) );
            
        break;
    #---------------------------------------------------------------------
        case 'deleteWorkorder' :
            $workorderId = $directPost[1];
            $woInfo = $WORKORDER->getById( $workorderId );
            if( $WORKORDER->deleteById( $workorderId, $woInfo['customerName'] ) )
            {
                if( $woInfo['workorder_type_id'] == 1 ) { # K.O.P
                    $serviceId = $WORKORDER->getServiceId( $workorderId );
                    $SERVICE_REPORT = new ServiceRapport( $ERR, $LOG );
                    $SERVICE_REPORT->deleteById( $serviceId['id'] );
                }
                print( "true" );
            } else { print( "false" ); }
        break;
    #---------------------------------------------------------------------
        case 'closeStatus' :
            $workorderID =  $directPost[1];
            
            # Update the ticket status by incereasing by one
            if( $WORKORDER->increaseStatus( $workorderID, $directPost[2] ) == true ) { 
                
                $STATUS = new Status( $ERR, $LOG );
                $statusData = $STATUS->getStatusNameColorById( intval($directPost[2]+1) );
                
                $WORKNOTE = new Worknote( $ERR, $LOG, $workorderID );
                $worknoteTxt = "Workorderstatus is geupdate naar status: '".$statusData['name']."' door ". $_SESSION['USER_NAME'];
                $WORKNOTE->save( $worknoteTxt );
                
                $woData = $WORKORDER->getById( $workorderID );

                print( "true" );  
            } else { print( "false" ); }

        break;
    #--------------------------------------------
        case 'deleteAttachment' :    
            $attachmentID = $directPost[1];
            $workorderID =  $directPost[2];
            
            # Get the file name of the attachment that's going to be deleted
            $WORKORDER_DATA = new WorkorderData( $ERR, $LOG, $workorderID );
            $fileName = $WORKORDER_DATA->getFileNameByFileId( $attachmentID );
            
            #delete the file from the database
            $WORKORDER_DATA->deleteAttachment( $attachmentID  );
            $workorderData = $WORKORDER->getAttachments( $workorderID );

            # Write a worknote for the workorder, concerning this delete
            $WORKNOTE = new Worknote( $ERR, $LOG, $workorderID );
            $worknoteTxt = "Bijlage ".$fileName['link']." is verwijderd door ". $_SESSION['USER_NAME'];
            $WORKNOTE->save( $worknoteTxt );
            
            $woData = $WORKORDER->getById( $workorderID );

            print( json_encode( $workorderData ) );
        break;
    #---------------------------------------------------------------------
        case 'saveInvoiceNumber' :
            $workorderID = $directPost[1];
            $invoiceNmbr = $directPost[2];
        
            # Instantiate the ticket_data class and run invoice caving method
            $WORKORDER_DATA = new WorkorderData( $ERR, $LOG, $workorderID );
            if( $WORKORDER_DATA->saveInvoiceNumber( $invoiceNmbr ) == true )
            {
                # Set worknote that invoice number has been attached
                $worknoteTxt = "Factuurnummer " . $invoiceNmbr ." is gekoppeld aan deze werkopdracht.";
                $WORKNOTE = new Worknote( $ERR, $LOG, $workorderID );
                $WORKNOTE->save( $worknoteTxt );
                
                $WORKORDER = new Workorder( $ERR, $LOG );
                $woData = $WORKORDER->getById( $workorderID );

                print( json_encode( true ) );
            } else {
                print( json_encode( false ) );
            }
        break;    
    #---------------------------------------------------------------------
        case 'getWorkorderTypes' :
            $workorderTypes = $WORKORDER->getTypes();
            print(json_encode( $workorderTypes ) );
        break;
    #---------------------------------------------------------------------
        case 'getUnplannedTickets' :
            $WORKORDER = new Workorder( $ERR, $LOG );
            $unplannedWorkorders = $WORKORDER->getUnplanned();
            print( json_encode( $unplannedWorkorders ) );
        break;
    #---------------------------------------------------------------------
        case 'getCompanybyWorkorderID' :
            $workorderID = $directPost[1];
            $WORKORDER_DATA = new WorkorderData( $ERR, $LOG, $workorderID );
            $woCompanyData = $WORKORDER_DATA->getComanyOfWorkorder();
            if( $woCompanyData['customer_id'] == $woCompanyData['location_id'] )
            {
                $woCompanyData['location'] = $woCompanyData['customer']; 
            }
            
            print( json_encode( $woCompanyData ) );
        break;
    #---------------------------------------------------------------------
        case 'getOpenWorkordersOfCustomer' :
            $customerID = $directPost[1];
            $WorkorderData = $WORKORDER->getWorkordersOfCustomer( $customerID );
            print( json_encode( $WorkorderData ) );
        break;
    #---------------------------------------------------------------------
        case 'getArchivedWorkordersOfCustomer' :
            $customerID = $directPost[1];
            $WorkorderData = $WORKORDER->getArchivedWorkordersOfCustomer( $customerID );
            print( json_encode( $WorkorderData ) );
        break;     
    #---------------------------------------------------------------------
        case 'getAppWorkordersByBusId' :
            $bus = $directPost[1];
            $key = $directPost[2];
            
            $workorders = $WORKORDER->getAppWorkordersByBusId( $bus, $key );
            print( json_encode( $workorders ) );
        break;
    #---------------------------------------------------------------------
        case 'getAppWorkorderById' :
            $bus = $directPost[1];
            $key = $directPost[2];
            $workorderID = $directPost[3];
            
            $workorderData = $WORKORDER->getAppWorkorderId( $bus, $key, $workorderID );
            
            $WORKNOTE = new Worknote( $ERR, $LOG, $workorderID );
            $worknotes = $WORKNOTE->getWorknotesOfWorkorder();
            
            $returnArray = array(
                'workorder' => $workorderData, 
                'worknotes' => $worknotes 
            );
             
            print( json_encode( $returnArray ) );
        break;
    #---------------------------------------------------------------------
        case 'appClosesWorkorder' :
            $bus = $directPost[1];
            $key = $directPost[2];
            $name = $directPost[3];
            $workorderID = $directPost[4];
            
            $USER = new User( $ERR, $LOG );
            $userId = $USER->getUserIdByBusId( $bus );
            
            $APP = new EngineerApp( $ERR, $LOG );
            if( $APP->verifyActivationCode( $key, $bus ) == "true" )
            {
                if( $WORKORDER->increaseStatus( $workorderID, 4 ) == true )
                {
                    $WORKNOTE = new Worknote($ERR, $LOG, $workorderID );
                    $WORKNOTE->setAppSource( $userId );
                    $worknoteTxt = $name." heeft deze werkopdracht via de Monteur App afgesloten.";
                    $WORKNOTE->save( $worknoteTxt );
                    
                    $woData = $WORKORDER->getById( $workorderID );

                    # Send new worknote notification to company
                    $sub = urldecode( "Werknotitie voor werkopdracht van ".$woData['customerName'] );
                    $msg = "Bus ".$bus." heeft een nieuwe werknotitie toegevoegd aan een werkopdracht van ". urldecode($woData['customerName'])." (WOD00".$workorderID.")<br><br>";
                    $msg.= "Werknotitie : <pre>".$worknoteTxt."</pre>";
                    $msg.= "<br><i><small>Dit bericht is automatisch gegenereerd door het Onderhoud Registratie Systeem MRS</small></i>";
                    $EMAIL->send(NEW_WORKNOTES_EMAIL_ADDRESS, $sub, $msg );
                    
                    $workorders = $WORKORDER->getAppWorkordersByBusId( $bus, $key );
                    print( json_encode( $workorders ) );
                }    
            }
            else { print( "INVALID_KEY" ); }
        break;
    #---------------------------------------------------------------------
    default :
        $LOG->LogError("Invalid Switch case proviced for Workorder Controller");
    }
}
else {
    $LOG->LogError("Invalid Direct Post provided for Workorder Controller");
}

?>