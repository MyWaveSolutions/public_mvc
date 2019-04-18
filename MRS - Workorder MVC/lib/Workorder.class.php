<?php

class Workorder
{
    private $_ERR = false;
    private $_LOG = false;
    
    // Only when source is app, the properties got a value
    private $_source = false;
    private $_userId = false;
    
    #---------------------------------------------------------------------
    public function __construct( ErrorHandling $ERR, KLogger $LOG )
    {
        $this->_ERR = $ERR;
        $this->_LOG = $LOG;
    }
    
    #---------------------------------------------------------------------
    public function create( $workorderData )
    {
        $conn = new PDO( DB_INFO, DB_USER, DB_PASS );
        $stmt = $conn->prepare("
            INSERT INTO workorders ( id, workorder_type_id, status_id, 
                order_id, customer_id, location_id, executed_by, 
                date_last_modified, last_modified_by, date_created, 
                created_by, status ) 
            VALUES (
                :id, :orderType, :statusId, :orderId, :customerId, :locationId, 
                :busId, :today, :userId, :today, :userId, :active )
        ");
        
        if( $stmt->execute( array( 
            ':id'         => null,
            ':orderType'  => $workorderData['workorderType'],
            ':statusId'   => 2,
            ':orderId'    => $workorderData['ticketID'],
            ':customerId' => $workorderData['customerID'],
            ':locationId' => $workorderData['locationID'],
            ':busId'      => 0,
            ':today'      => date("Y-m-d H:i:s"),
            ':userId'     => $workorderData['createdBy'],
            ':active'     => 1,  
        ) ) ) { return $conn->lastInsertId(); }
        else
        {
            $errMsg = "PDO execute error while trying to create workorder for orderID ". $workorderData['ticketID'] ." (order type:".$workorderData['workorderType'].") by userID ". $_SESSION['USER_ID'];
            $this->_LOG->LogError( $errMsg );
            $this->_ERR->getError( 'mail', 335, $errMsg );
            return false; 
        }
    }
    
    #---------------------------------------------------------------------
    public function getById( $workOrderId )
    {
        $conn = new PDO( DB_INFO, DB_USER, DB_PASS );
        $stmt = $conn->prepare("
            SELECT 
                A.id, A.customer_id, A.location_id, A.workorder_type_id, 
                A.order_id, A.status_id, A.executed_by,
                B.comp_name as customerName, 
                B.cont_name as contactPerson, 
                B.cont_phone as contactPhone,
                C.name as locationName, 
                D.start_date, D.start_time, D.end_time,
                A.date_created as dateCreated, 
                A.created_by,
                CONCAT(E.firstname,' ', E.lastname) as createdBy,
                G.name as busName, H.name as statusName, I.name as orderTypeName
            FROM workorders A
            INNER JOIN sys_customers B ON B.id = A.customer_id
            LEFT JOIN sys_locations C ON C.id = A.location_id
            LEFT JOIN sys_planning D ON D.workorder_id = A.id 
            LEFT JOIN tickets F ON (
                F.userTypeID = 2 
                AND A.workorder_type_id = 2 
                AND A.order_id = F.id  )
            LEFT JOIN sys_users E ON A.created_by = E.id
            LEFT JOIN sys_bus G ON G.id = A.executed_by
            LEFT JOIN status H ON H.id = A.status_id
            LEFT JOIN sys_order_types I ON I.id = A.workorder_type_id
            WHERE A.id = :workorderID
        ");
        
        if( $stmt->execute( array( ':workorderID' => $workOrderId ) ) ) 
        {    
            $result = $stmt->fetch( PDO::FETCH_ASSOC );
            if( !empty( $result ) ) { return $result; }
            else {
                $errMsg = "Database returned an empty result while requesting for workorderID ".$workOrderId;
            }    
        } 
        else {
            $errMsg = "PDO execute error occurred while trying to retrieve workorder with ID ".$workOrderId;
        }
        
        $this->_LOG->LogError( $errMsg);
        $this->_ERR->getError( 'mail',338, $errMsg );
        return false;
    }

    #---------------------------------------------------------------------
    public function getOpenWorkordersInOrderOfStatus( $order )
    {
        # Establish database connection and set SQL select statement
        $conn = new PDO( DB_INFO, DB_USER, DB_PASS );

        $query = "SELECT A.id, A.status_id, A.date_created, A.date_last_modified, I.status_img, IF(workorder_type_id = 1, 'preventief onderhoud', C.description) as description, E.comp_name as customer, CONCAT(G.firstname, ' ', G.lastname) as user, H.name as orderType, J.priority as prioValue ";
        $query.= " FROM workorders A";
        $query.= " LEFT JOIN maintenance B ON (workorder_type_id = 1 AND A.order_id = B.id)";
        $query.= " LEFT JOIN tickets C ON (workorder_type_id != 1 AND A.order_id = C.id)";
        $query.= " LEFT JOIN sys_customers E ON E.id = A.customer_id";
        $query.= " LEFT JOIN sys_bus F ON F.id = A.executed_by ";
        $query.= " LEFT JOIN sys_users G on G.id = A.created_by";
        $query.= " INNER JOIN sys_order_types H ON H.id = A.workorder_type_id";
        $query.= " INNER JOIN status I ON  I.id = A.status_id ";
        $query.= " LEFT JOIN workorders_priority J ON J.workorders_id = A.id";
        $query.= " WHERE A.status_id < :maxOrderStatus";
        $query.= " AND A.status = :activeStatus";
        $query.= " ORDER BY A.status_id ". $order;

        $stmt = $conn->prepare( $query );

        if( $stmt->execute( array(
            ':maxOrderStatus' => 6,
            ':activeStatus' => 1
        ) ) ) {
            $result = $stmt->fetchAll( PDO::FETCH_ASSOC );
            if( !empty( $result ) ){ return $result; }
            else {
                $this->_LOG->LogInfo("Database returned empty result when trying to retrieve ordered open ticket workorders by userID ". $_SESSION['USER_ID']);
            }
        }
        else {
            $errMsg = "PDO execute failure while trying to retrieve ordered open ticket workorders from the database by userID ". $_SESSION['USER_ID'];
            $this->_LOG->LogError( $errMsg );
            $this->_ERR->getError( 'mail', 360, $errMsg );
            return false;
        }
    }

    #---------------------------------------------------------------------
    public function getByStatusId( $statusId)
    {
        # Establish database connection and set SQL select statement
        $conn = new PDO( DB_INFO, DB_USER, DB_PASS );
        $stmt = $conn->prepare("
            SELECT A.id, A.status_id, A.date_created, A.date_last_modified, 
                I.status_img, 
            IF(workorder_type_id = 1, \"preventief onderhoud\", C.description) as description,
            E.comp_name as customer, CONCAT(G.firstname, ' ', G.lastname) as user, 
            H.name as orderType, J.priority as prioValue
            FROM workorders A
            LEFT JOIN maintenance B ON (workorder_type_id = 1 AND A.order_id = B.id)
            LEFT JOIN tickets C ON (workorder_type_id != 1 AND A.order_id = C.id)
            LEFT JOIN sys_customers E ON E.id = A.customer_id
            LEFT JOIN sys_bus F ON F.id = A.executed_by 
            LEFT JOIN sys_users G on G.id = A.created_by
            INNER JOIN sys_order_types H ON H.id = A.workorder_type_id 
            INNER JOIN status I ON  I.id = A.status_id 
            LEFT JOIN workorders_priority J ON J.workorders_id = A.id 
            WHERE A.status_id = :statusId
            AND A.status = :activeStatus
            ORDER BY A.id DESC
        ");

        if( $stmt->execute( array(
            ':statusId' => $statusId,
            ':activeStatus' => 1
        ) ) ) {
            $result = $stmt->fetchAll( PDO::FETCH_ASSOC );
            if( !empty( $result ) ){ return $result; }
            else {
                $this->_LOG->LogInfo("Database returned empty result when trying to retrieve open ticket of status ID ". $statusId);
            }
        }
        else {
            $errMsg = "PDO execute failure while trying to retrieve open ticket of status ID ". $statusId;
            $this->_LOG->LogError( $errMsg );
            $this->_ERR->getError( 'mail', 360, $errMsg );
            return false;
        }
    }
    
    #---------------------------------------------------------------------
    public function getOpenWorkorders()
    {
        # Establish database connection and set SQL select statement
        $conn = new PDO( DB_INFO, DB_USER, DB_PASS );
        $stmt = $conn->prepare("
            SELECT A.id, A.status_id, A.date_created, A.date_last_modified, 
                I.status_img, 
            IF(workorder_type_id = 1, \"preventief onderhoud\", C.description) as description,
            E.comp_name as customer, CONCAT(G.firstname, ' ', G.lastname) as user, 
            H.name as orderType, J.priority as prioValue
            FROM workorders A
            LEFT JOIN maintenance B ON (workorder_type_id = 1 AND A.order_id = B.id)
            LEFT JOIN tickets C ON (workorder_type_id != 1 AND A.order_id = C.id)
            LEFT JOIN sys_customers E ON E.id = A.customer_id
            LEFT JOIN sys_bus F ON F.id = A.executed_by 
            LEFT JOIN sys_users G on G.id = A.created_by
            INNER JOIN sys_order_types H ON H.id = A.workorder_type_id 
            INNER JOIN status I ON  I.id = A.status_id 
            LEFT JOIN workorders_priority J ON J.workorders_id = A.id 
            WHERE A.status_id < :maxOrderStatus
            AND A.status = :activeStatus
            ORDER BY A.date_last_modified DESC
        ");
        
        if( $stmt->execute( array( 
            ':maxOrderStatus' => 6,
            ':activeStatus' => 1
        ) ) ) {
            $result = $stmt->fetchAll( PDO::FETCH_ASSOC );
            if( !empty( $result ) ){ return $result; }
            else {
                $this->_LOG->LogInfo("Database returned empty result when trying to retrieve open ticket workorders by userID ". $_SESSION['USER_ID']);
            }        
        }
        else {
            $errMsg = "PDO execute failure while trying to retrieve open ticket workorders from the database by userID ". $_SESSION['USER_ID'];       
            $this->_LOG->LogError( $errMsg );
            $this->_ERR->getError( 'mail', 360, $errMsg );
            return false;   
        } 
    }
    
    #---------------------------------------------------------------------
    public function getAttachments( $workorderId )
    {
        # Establish database connection and set SQL select statment
        $conn = new PDO( DB_INFO, DB_USER, DB_PASS );
        $stmt = $conn->prepare('
            SELECT id, link, url FROM workorders_attachments 
            WHERE workorders_id = :id
        ');
        
        # Execute the prepared PDO SQL select statment.
        $stmt->execute( array( ':id' => $workorderId ) );
        $result = $stmt->fetchAll( PDO::FETCH_ASSOC );
        
        # Return the fetched attachment or error flag false
        return !empty( $result ) ? $result : false;
    } 
    
    #---------------------------------------------------------------------
    public function setAppSource( $userId )
    {
        $this->_source = 'app';
        $this->_userId = $userId;
    }
    
    #---------------------------------------------------------------------
    public function increaseStatus( $workorderId, $statusID )
    {
        if( $this->_source == 'app' ){ $userId = $this->_userId; } 
        else { $userId = $_SESSION['USER_ID']; }
        
        if( intval( $statusID == 2 ) ){ $this->_setCreatedBy( $workorderId ); }
        
        # Establish database connection and set PDO SQL update
        $conn = new PDO( DB_INFO, DB_USER, DB_PASS );
        $stmt = $conn->prepare('
            UPDATE workorders 
            SET status_id = status_id + 1, 
                date_last_modified = :today, 
                last_modified_by = :userId 
            WHERE id = :id AND status_id < 6
        ');
        
        # Execute prepared PDO SQL update statement
        if( $stmt->execute( array( 
            ':id'       => intval( $workorderId ), 
            ':today'    => date('Y-m-d H:i:s'),
            ':userId'   => $userId
        ) ) ) {
            $infoMsg = "Status of workorder ". $workorderId. " was updated from statusID ". $statusID . " to statusID ".intval($statusID + 1)." by userID ".$_SESSION['USER_ID'];
            $this->_LOG->LogInfo( $infoMsg );
            return true;    
        } else {
            $errMsg = "PDO execute error occurred while trying to update workorder ". $workorderId. " from statusID ". $statusID . " to statusID ".intval($statusID + 1)." by userID ".$_SESSION['USER_ID'];
            $this->_LOG->LogError($errMsg);
            $this->_ERR->getError( 'mail', 347, $errMsg );
            return false; 
        }
    }
    
    #---------------------------------------------------------------------
    public function _setCreatedBy( $workorderId )
    {
        $woData = $this->getById( $workorderId );
        
        if( intval( $woData['created_by'] == 0 ))
        {
            $conn = new PDO( DB_INFO, DB_USER, DB_PASS );
            $stmt = $conn->prepare(" 
                UPDATE workorders 
                set created_by = :activeUser
                WHERE id = :workorderID
            "); 
            
            if( $stmt->execute( array( 
                ':activeUser'  => $_SESSION['USER_ID'],
                ':workorderID' => $workorderId
            ) ) ) {
                $this->_LOG->LogInfo("workorder with ID ". $workorderId. " has been approved by user ". $_SESSION['USER_NAME']." (ID:". $_SESSION['USER_ID'].")" );
                return true;
            }   
            else {
                $errMsg = "PDO execute Error while trying to aprove workorder with ID ".$workorderId." by user ". $_SESSION['USER_NAME']. "(ID:". $_SESSION['USER_ID'].")";
                $this->_LOG->LogError( $errMsg );
                $this->_ERR->getError( 'mail', 387, $errMsg ); 
                return false;       
            }
        }    
    }
    
    #---------------------------------------------------------------------
    public function getArchived()
    {
        # Establish database connection and set SQL select statement
        $conn = new PDO( DB_INFO, DB_USER, DB_PASS );
        $stmt = $conn->prepare("
            SELECT A.id, A.status_id, A.date_created, A.date_last_modified, 
                I.status_img, 
            IF(workorder_type_id = 1, \"preventief onderhoud\", C.description) as description,
            E.comp_name as customer, CONCAT(G.firstname, ' ', G.lastname) as user, 
            H.name as orderType, E.id as busId, J.priority as prioValue
            FROM workorders A
            LEFT JOIN maintenance B ON (workorder_type_id = 1 AND A.order_id = B.id)
            LEFT JOIN tickets C ON (workorder_type_id != 1 AND A.order_id = C.id)
            LEFT JOIN sys_customers E ON E.id = A.customer_id
            LEFT JOIN sys_bus F ON F.id = A.executed_by 
            LEFT JOIN sys_users G on G.id = A.created_by
            INNER JOIN sys_order_types H ON H.id = A.workorder_type_id 
            INNER JOIN status I ON  I.id = A.status_id 
            LEFT JOIN workorders_priority J ON J.workorders_id = A.id 
            WHERE A.status_id = :orderStatus
            AND A.status = :active
            ORDER BY A.date_last_modified DESC
        ");
        
        # Execute the prepared PDO SQL select statement
        $stmt->execute( array( ':orderStatus' => 6, ':active' => 1 ) );
        $result = $stmt->fetchAll( PDO::FETCH_ASSOC );
        
        # Return the result or send error flag when result is empty.
        return ( !empty( $result ) ) ? $result : false;  
    }
    
    #---------------------------------------------------------------------
    public function getWorkordersOfCustomer( $customerID )
    {
        # Establish database connection and set SQL select statement
        $conn = new PDO( DB_INFO, DB_USER, DB_PASS );
        $stmt = $conn->prepare("
            SELECT A.id, A.status_id, A.date_created, H.status_img, D.comp_name as customer,
            IF(workorder_type_id = 1, \"preventief onderhoud\", C.description) as description,
            CONCAT(F.firstname, ' ', F.lastname) as user, G.name as orderType, E.id as busId, I.priority as prioValue
            FROM workorders A
            LEFT JOIN maintenance B ON (workorder_type_id = 1 AND A.order_id = B.id)
            LEFT JOIN tickets C ON (workorder_type_id != 1 AND A.order_id = C.id)
            LEFT JOIN sys_customers D ON D.id = A.customer_id 
            LEFT JOIN sys_bus E ON A.executed_by = E.id
            LEFT JOIN sys_users F on F.id = A.created_by
            INNER JOIN sys_order_types G ON G.id = A.workorder_type_id
            INNER JOIN status H ON H.id = A.status_id 
            LEFT JOIN workorders_priority I ON I.workorders_id = A.id 
            WHERE A.customer_id = :customerID
            AND A.status_id < :closedStatus
            AND A.status = :active
            ORDER BY A.order_id DESC
        ");
        
        # Execute the prepared PDO SQL select statement
        if( $stmt->execute( array( 
            ':customerID' => intval( $customerID ),
            ':closedStatus' => 6,
            ':active' => 1
        ) ) ) {
            $result = $stmt->fetchAll( PDO::FETCH_ASSOC );
            if( !empty( $result ) ) { return $result; }
            else { return false; }    
        }
        else {
            $errMsg = "PDO execute error while trying to get workorders from CustomerID ". $customerID;
            $this->_LOG->LogError( $errMsg );
            $this->_ERR->getError( 'mail', 348, $errMsg );
            return false;                
        }  
    }
    
    #---------------------------------------------------------------------
    public function getArchivedWorkordersOfCustomer( $customerID )
    {
        # Establish database connection and set SQL select statement
        $conn = new PDO( DB_INFO, DB_USER, DB_PASS );
        $stmt = $conn->prepare("
            SELECT A.id, A.status_id, A.date_created, H.status_img, D.comp_name as customer,
            IF(workorder_type_id = 1, \"preventief onderhoud\", C.description) as description,
            CONCAT(F.firstname, ' ', F.lastname) as user, G.name as orderType, E.id as busId, I.priority as prioValue
            FROM workorders A
            LEFT JOIN maintenance B ON (workorder_type_id = 1 AND A.order_id = B.id)
            LEFT JOIN tickets C ON (workorder_type_id != 1 AND A.order_id = C.id)
            LEFT JOIN sys_customers D ON D.id = A.customer_id 
            LEFT JOIN sys_bus E ON A.executed_by = E.id
            LEFT JOIN sys_users F on F.id = A.created_by
            INNER JOIN sys_order_types G ON G.id = A.workorder_type_id
            INNER JOIN status H ON H.id = A.status_id 
            LEFT JOIN workorders_priority I ON I.workorders_id = A.id 
            WHERE A.customer_id = :customerID
            AND A.status_id > :closedStatus
            AND A.status = :active
            ORDER BY A.order_id DESC
        ");
        
        # Execute the prepared PDO SQL select statement
        if( $stmt->execute( array( 
            ':customerID' => intval( $customerID ),
            ':closedStatus' => 5,
            ':active' => 1
        ) ) ) {
            $result = $stmt->fetchAll( PDO::FETCH_ASSOC );
            if( !empty( $result ) ) { return $result; }
            else { 
                $this->_LOG->LogInfo("Database returned an empty result while trying to retrieve archived workorders for customer ID ". $customerID );
                return false; 
            }    
        }
        else {
            $errMsg = "PDO execute error while trying to get archived workorders from CustomerID ". $customerID;
            $this->_LOG->LogError( $errMsg );
            $this->_ERR->getError( 'mail', 372, $errMsg );
            return false;                
        }  
    } 
    
    #---------------------------------------------------------------------
    # NEED REBUILD!
    public function getWorkorderByInvoiceNumber( $invoiceNumber )
    {
        # Establish database connection and set SQL select statement
        $conn = new PDO( DB_INFO, DB_USER, DB_PASS );
        $stmt = $conn->prepare("
            SELECT A.id, status_id, A.date_last_modified, B.date_created, 
                B.description, C.comp_name as customer, 
                CONCAT(D.firstname, ' ', D.lastname) as user, E.status_img, 
                F.name as orderType, H.priority as prioValue
            FROM workorders A
            LEFT JOIN tickets B ON A.order_id = B.id
            LEFT JOIN sys_customers C ON B.customer_id = C.id
            LEFT JOIN sys_users D ON B.created_by = D.id
            LEFT JOIN status E on A.status_id = E.id
            LEFT JOIN sys_order_types F ON A.workorder_type_id = F.id
            LEFT JOIN sys_invoices G ON A.id = G.workorders_id
            LEFT JOIN workorders_priority H ON H.workorders_id = A.id 
            WHERE B.customer_id = :customerID
            ORDER BY A.order_id DESC
        ");
        
        # Execute the prepared PDO SQL select statement
        $stmt->execute( array( ':invoiceNumber' => intval( $invoiceNumber ) ) );
        $result = $stmt->fetchAll( PDO::FETCH_ASSOC );
        
        # Return the fetched ticket data or error flag false
        return !empty( $result ) ? $result : false;
    }
    
    #---------------------------------------------------------------------
    public function getTypes()
    {
        $conn = new PDO( DB_INFO, DB_USER, DB_PASS );
        $stmt = $conn->prepare("SELECT * FROM sys_order_types");
        
        if( $stmt->execute() ){
            $result = $stmt->fetchAll( PDO::FETCH_ASSOC );
            if( !empty( $result ) ){ return $result; }
            else { 
                $errMsg = "database returned an empty result while trying toretrieve workorder types from the database.";
            }
        }
        else {
            $errMsg = "PDO execute error while trying toretrieve workorder types from the database.";
        }
        
        $this->_LOG->LogError( $errMsg );
        $this->_ERR->getError( 'mail', 350, $errMsg );
        return false;
    }
    
     #---------------------------------------------------------------------
    public function getUnplanned()
    {
        $conn = new PDO( DB_INFO, DB_USER, DB_PASS );
        $stmt = $conn->prepare("
            SELECT A.id, C.comp_name as customerName 
            FROM workorders A 
            LEFT JOIN sys_customers C ON C.id = A.customer_id
            LEFT JOIN tickets D ON D.id = A.order_id
            WHERE A.workorder_type_id = :ticketType 
            AND D.planned = :unplanned
            AND A.status = :activeStatus
            AND A.status_id < :openStatus
        ");
        
        if( $stmt->execute( array( 
            ':unplanned'    => 0,
            ':ticketType'   => 2,
            ':openStatus'   => 5,
            ':activeStatus' => 1
        ) ) ) {
            $result = $stmt->fetchAll( PDO::FETCH_ASSOC );
            if( !empty( $result ) ){ return $result; }
            else { 
                $errMsg = "database returned an empty result while trying to retrieve unplanned tickets.";
            }
        }
        else {
            $errMsg = "PDO execute error while trying to retrieve unplanned workorder from the database.";
        }
        
        $this->_LOG->LogError( $errMsg );
        $this->_ERR->getError( 'mail', 351, $errMsg );
        return false;
    }
    
    #---------------------------------------------------------------------
    public function getPlannedWorkorders()
    {
        $conn = new PDO( DB_INFO, DB_USER, DB_PASS );
        $stmt = $conn->prepare("
            SELECT 
                A.id, A.customer_id, A.location_id, 
                B.start_date, B.start_time, B.end_time, 
                C.comp_name as comp_name, C.comp_address as comp_address, C.comp_city as comp_city, 
                D.address as location_address, D.city as location_city,
                E.name as bus, E.id as busId, F.priority as prioValue
            FROM workorders A
            INNER JOIN sys_planning B ON B.workorder_id = A.id  
            INNER JOIN sys_customers C ON C.id = A.customer_id
            LEFT JOIN sys_locations D ON D.id = A.location_id
            INNER JOIN sys_bus E ON E.id = A.executed_by 
            LEFT JOIN workorders_priority F ON F.workorders_id = A.id            
            WHERE A.status = :activeStatus
            AND A.status_id < :openStatus
            AND B.start_date >= :today
            ORDER BY B.start_date ASC, B.start_time ASC
        ");
        
        if( $stmt->execute( array( 
            ':openStatus'   => 6,
            ':activeStatus' => 1,
            ':today' => date("Y-m-d")
        ) ) ) {
            $result = $stmt->fetchAll( PDO::FETCH_ASSOC );
            if( !empty( $result ) ){ return $result; }
            else { 
                $errMsg = "database returned an empty result while trying to retrieve planned workorders.";
            }
        }
        else {
            $errMsg = "PDO execute error while trying to retrieve planned workorders from the database.";
            $this->_ERR->getError( 'mail', 359, $errMsg );
        }
        
        $this->_LOG->LogError( $errMsg );
        return false;
    }
    
    #---------------------------------------------------------------------
    public function getWorkordersByDate( $date )
    {
        $conn = new PDO( DB_INFO, DB_USER, DB_PASS );   
        $stmt = $conn->prepare("
            SELECT 
                A.id, A.customer_id, A.location_id, 
                B.start_date, B.start_time, B.end_time, 
                C.comp_name as comp_name, C.comp_address as comp_address, C.comp_city as comp_city, 
                D.address as location_address, D.city as location_city,
                E.name as bus, E.id as busId, F.priority as prioValue
            FROM workorders A
            INNER JOIN sys_planning B ON B.workorder_id = A.id  
            INNER JOIN sys_customers C ON C.id = A.customer_id
            LEFT JOIN sys_locations D ON D.id = A.location_id
            INNER JOIN sys_bus E ON E.id = A.executed_by  
            LEFT JOIN workorders_priority F ON F.workorders_id = A.id    
            WHERE B.start_date = :date
            AND A.status_id < :openWorkorder
            ORDER BY F.priority ASC, E.name ASC
        ");     
        
        if( $stmt->execute( array( 
            ':date' => $date, ':openWorkorder' => 6 
        ) ) ){
            $result = $stmt->fetchAll( PDO::FETCH_ASSOC );
            if( !empty( $result ) ){  return $result; }
            else {  
                $infoMsg = "Database returned an empty result when requesting planned workorders for date ". $date;
                $this->_LOG->LogInfo( $infoMsg );
                return false;
            }
        }
        else
        {
            $errMsg = "PDO execute error while trying to retrieve planned workorders for date ". $date;
            $this->_LOG->LogError( $errMsg );
            $this->_ERR->getError( 'mail', 362, $errMsg );
            return false;
        }
    }
    
    #---------------------------------------------------------------------
    public function deleteKopById( $workorderId )
    {
        $conn = new PDO( DB_INFO, DB_USER, DB_PASS );
        $stmt = $conn->prepare("
            DELETE FROM workorders 
            WHERE id = :workorderID 
            AND workorder_type_id = :KopType
        ");
        
        if( $stmt->execute( array( 
            ':workorderID' => $workorderId, ':KopType' => 1
        ) ) ) {
            $this->_LOG->LogInfo("Workorder with ID ". $workorderId. " was deleted from the database by user ". $_SESSION['USER_NAME']. " (ID:". $_SESSION['USER_ID']. ")");
            return true;
        }
        else {
            $errMsg = "PDO execute error while trying to delete workorder with ID ". $workorderId." form the database by user ". $_SESSION['USER_NAME']. " (ID:". $_SESSION['USER_ID']. ")";
            $this->_LOG->LogError( $errMsg );
            $this->_ERR->getError( 'mail', 369, $errMsg );
            return false;
        }
    }
    
    #---------------------------------------------------------------------
    # this method only check whether the customer has open workorders. This
    # method is not used as treival of customer workorders for search engine
    public function getOpenWorkordersByCustomerId( $customerName, $customerID )
    {
        $conn = new PDO( DB_INFO, DB_USER, DB_PASS );
        $stmt = $conn->prepare("
            SELECT A.id, A.customer_id, 
            D.comp_name as comp_name, D.comp_address as comp_address, 
            D.comp_city as comp_city, E.priority as prioValue 
            FROM workorders A 
            LEFT JOIN maintenance B ON (workorder_type_id = 1 AND A.order_id = B.id) 
            LEFT JOIN tickets C ON (workorder_type_id != 1 AND A.order_id = C.id)             
            LEFT JOIN sys_customers D ON D.id = A.customer_id 
            LEFT JOIN workorders_priority E ON E.workorders_id = A.id 
            WHERE A.customer_Id = :customerId
            AND A.status_id < :openWorkorder   
        ");
        
        if( $stmt->execute( array( 
            ':openWorkorder' => 6,
            ':customerId' =>  $customerID
        ) ) ) {
            $result = $stmt->fetchAll( PDO::FETCH_ASSOC );
            if( !empty($result ) )
            {
                $results = count( $result );
                $infoWarning = "Klant ". $customerName. "(ID:". $customerID.") kon niet verwijderd worden omdat er nog ". $results. " werkopdracht(en) bij deze klant openstaan.";
                $this->_LOG->LogInfo( $infoWarning );
                return $infoWarning;
            }
            $this->_LOG->LogInfo("No open workorders found for Customer". $customerName. "(ID:".$customerID.")" );
            return "true";
        }
        else {
            $errMsg = "PDO execute error while trying to retrieve open workorders for Customer". $customerName. "(ID:".$customerID.")";
            $this->_LOG->LogError( $errMsg );
            $this->_ERR->getError( 'mail', 374, $errMsg );
            return "false";
        }    
    }
    
    #---------------------------------------------------------------------
    public function getAppWorkordersByBusId ( $busId, $key )
    {
        $conn = new PDO( DB_INFO, DB_USER, DB_PASS );   
        $stmt = $conn->prepare("
            SELECT A.id, A.customer_id as customer_id, A.location_id as location_id,  D.id as busId,  
                B.comp_name as comp_name, B.comp_address as comp_address, B.comp_city as comp_city,
                C.address as location_address, C.city as location_city, E.priority as prioValue
            FROM workorders A  
            LEFT JOIN sys_customers B ON B.id = A.customer_id 
            LEFT JOIN sys_locations C ON C.id = A.location_id
            LEFT JOIN sys_bus D ON D.id = A.executed_by
            LEFT JOIN workorders_priority E ON E.workorders_id = A.id 
            LEFT JOIN sys_planning F ON F.workorder_id = A.id
            WHERE A.executed_by = :busId
            AND A.status_id = :inProgress
            AND D.app_code = :key
            AND F.start_date = :today
            ORDER BY E.priority ASC
        ");     
        
        if( $stmt->execute( array( 
            ':busId' => $busId, 
            ':inProgress' => 4,
            ':key' => $key,
            ':today' => date("Y-m-d")
        ) ) ){
            $result = $stmt->fetchAll( PDO::FETCH_ASSOC );
            if( !empty( $result ) ){  return $result; }
            else {  
                $infoMsg = "Database returned an empty result when requesting planned workorders for date ". date("Y-m-d");
                $this->_LOG->LogInfo( $infoMsg );
                return false;
            }
        }
        else
        {
            $errMsg = "PDO execute error while trying to retrieve planned workorders for date ". date("Y-m-d");
            $this->_LOG->LogError( $errMsg );
            $this->_ERR->getError( 'mail', 362, $errMsg );
            return false;
        }
    }
    
    #---------------------------------------------------------------------
    public function getAppWorkorderId( $busId, $keyId, $workorderID )
    {
        $conn = new PDO( DB_INFO, DB_USER, DB_PASS );   
        $stmt = $conn->prepare("
            SELECT A.id, A.customer_id as customer_id, A.location_id as location_id, D.id as busId,  
                B.comp_name, B.comp_address, B.cont_phone as contactPhone, B.cont_name as contactName, 
                C.address as location_address, C.city as location_city, 
                IF(A.workorder_type_id = 1, \"preventief onderhoud\", E.description) as description,
                IF(A.workorder_type_id = 1, \"Op de tuin mag niet gerookt worden.\", E.comment) as comment
            FROM workorders A  
            LEFT JOIN sys_customers B ON B.id = A.customer_id 
            LEFT JOIN sys_locations C ON C.id = A.location_id
            LEFT JOIN sys_bus D ON D.id = A.executed_by
            LEFT JOIN tickets E ON (workorder_type_id != 1 AND A.order_id = E.id) 
            WHERE A.id = :workorderID 
            AND A.executed_by = :busId
            AND D.app_code = :key
            AND A.status = :active
        ");     
        
        if( $stmt->execute( array( 
            ':busId' => $busId,
            ':key' => $keyId,
            ':active' => 1,
            ':workorderID' => $workorderID
        ) ) ){
            $result = $stmt->fetch( PDO::FETCH_ASSOC );
            if( !empty( $result ) ){  return $result; }
            else {  
                $infoMsg = "Database returned an empty result when requesting workorders for Bus ID ". $busId;
                $this->_LOG->LogInfo( $infoMsg );
                return false;
            }
        }
        else
        {
            $errMsg = "PDO execute error while trying to retrieve workorders for Bus ID". $busId;
            $this->_LOG->LogError( $errMsg );
            $this->_ERR->getError( 'mail', 362, $errMsg );
            return false;
        }
    }

    #---------------------------------------------------------------------
    public function deleteById( $woId, $customer )
    {
        $conn = new PDO( DB_INFO, DB_USER, DB_PASS );
        $stmt = $conn->prepare("
          UPDATE workorders 
          SET status = :inactive
          WHERE id = :woId" );

        if( $stmt->execute( array( ':inactive' => 0, ':woId' => $woId ) ) ) {
            $this->_LOG->LogInfo("Workorder from " . $customer. " with ID WOD000". $woId. " has been deleted by ".$_SESSION['USER_NAME'] );
            return true;
        } else {
            $this->_LOG->LogError("PDO execute failure while trying to delete workorder from " . $customer. " with ID WOD000". $woId. " has been deleted by ".$_SESSION['USER_NAME'] );
            return false;
        }
    }

    #---------------------------------------------------------------------
    public function getServiceId( $woId )
    {
        $conn = new PDO( DB_INFO, DB_USER, DB_PASS );
        $stmt = $conn->prepare("
            SELECT A.id from sys_servicerapport A
            INNER JOIN maintenance B ON A.id = B.servicerapport_id
            INNER JOIN workorders C ON C.order_id = B.id
            WHERE C.id = :woID
            AND workorder_type_id = :kopType
        ");

        if( $stmt->execute( array( ':woID' => $woId, ':kopType' => 1 ) ) )
        {
            $result = $stmt->fetch( PDO::FETCH_ASSOC );
            if( !empty( $result ) ){  return $result; }
            else {
                $this->_LOG->LogInfo("Database returned an empty result when trying to retrieve serviceID for workorder ID ". $woId);
                return "empty";
            }
        } else {
            $this->_LOG->LogError("PDO execute failure hile trying to recieve serviceID for workorder ID ". $woId );
            return false;
        }
    }
}


?>