//------------------------------------------------------------------------
function getWorkOrderData( workorderID )
{
    // Make sure, no other XMLHttpRequest is interfering
    XMLHttpRequestObjectGWOD = false;
    
    // Set new XMLHttpRequestObjectGTD Object
    if(window.XMLHttpRequest) {
        XMLHttpRequestObjectGWOD = new XMLHttpRequest(); 
    } else if(window.ActiveXObject) {
        XMLHttpRequestObjectGWOD = new ActiveXObject("Microsoft.XMLHTTP");
    }
    // Connect the XMLHttpRequestObjectBUF with the Back-end file
    if( XMLHttpRequestObjectGWOD){
        XMLHttpRequestObjectGWOD.open("POST" , "../controllers/Workorder.controller.php");
        XMLHttpRequestObjectGWOD.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        // Verify the status of the XMLHttpRequestObjectGWOD
        XMLHttpRequestObjectGWOD.onreadystatechange = function()
        { 
            if( XMLHttpRequestObjectGWOD.readyState === 4  &&
                XMLHttpRequestObjectGWOD.status === 200)
            {    
                console.log("workorderResp : " + XMLHttpRequestObjectGWOD.responseText);
                
                // Fetch the server response (JSON String)
                var woResp = JSON.parse(XMLHttpRequestObjectGWOD.responseText);
                
                if( woResp.orderType == 1 ){ 
                    setMaintenanceForm( woResp['maintenance'],woResp['workorder'].id ); 
                }
                else {
                   setTicketForm( woResp['ticket'], woResp['ticketImages'], woResp['callImages'], woResp['workorder'] );
                }
                
                // run function for showing the form
                setWorkorderForm( woResp ); 
            }
        }
        // Set data for invoking backend controller
        directPost = new Array();
        directPost[0] = "getWorkorderData";
        directPost[1] = workorderID;

        // Send data array to Back-end
        XMLHttpRequestObjectGWOD.send("directPost=" + directPost);
    }
}

//------------------------------------------------------------------------
function setMaintenanceForm( kopData, workorderID )
{
    document.getElementById("tCustomer").innerHTML = decodeURIComponent( kopData.customerName );
    var trgtSpan = document.getElementById("printPdfSpan");
    var newBtn = document.createElement("button");
    newBtn.innerHTML = "Uitvoeringsbon printen";
    newBtn.setAttribute('onclick', 'openKopPDFpage(' + workorderID + ')');    
    trgtSpan.appendChild( newBtn );

    if( kopData.customer_id == kopData.location_id ){ 
         var locationName = kopData.customerName; 
    } else { locationName = kopData.locationName; }
    
    document.getElementById("tWoID").innerHTML = setStoringNummerPattern( workorderID );
    document.getElementById("tLocation").innerHTML = decodeURIComponent( locationName );
    document.getElementById("tContactPers").innerHTML = decodeURIComponent( kopData.contactPerson );
    document.getElementById("tPhone").innerHTML = decodeURIComponent( kopData.contactPhone );
    document.getElementById("tCreated").innerHTML = decodeURIComponent( kopData.dateCreated );
    document.getElementById("tCreatedBy").innerHTML = decodeURIComponent( kopData.createdBy );
    document.getElementById("tDateExec").innerHTML = decodeURIComponent( kopData.start_date );
    document.getElementById("tTimeExec").innerHTML = decodeURIComponent( "Van " + kopData.start_time + " tot " + kopData.end_time );
    document.getElementById("createdByLabel").innerHTML = "Ingepland door";
    document.getElementById("callHeadSpan").innerHTML = "K.O.P. ONDERHOUDSBEURT";
    
    // Remove rows we only need for tickets
    var table = document.getElementById("originalCallTbl");
    for( loop=0; loop<4; loop++ ){
        var rowCount = table.rows.length;
        table.deleteRow(rowCount -1);
    }
}

//------------------------------------------------------------------------
function setTicketForm( ticketData, ticketImages, callImages, workorder )
{  
    var trgtSpan = document.getElementById("printPdfSpan");
    var newBtn = document.createElement("button");
    newBtn.innerHTML = "Uitvoeringsbon printen";
    newBtn.setAttribute('onclick', 'openUrenPDFpage(' + workorder.id + ')');    
    trgtSpan.appendChild( newBtn );
    
    if( workorder.customer_id == workorder.location_id ){ 
         var locationName = workorder.customerName; 
    } else { locationName = workorder.locationName; }
    
    document.getElementById("tWoID").innerHTML = setStoringNummerPattern( workorder.id );
    document.getElementById("tCustomer").innerHTML = decodeURIComponent( workorder.customerName);
    document.getElementById("tLocation").innerHTML = decodeURIComponent( locationName );
    document.getElementById("tContactPers").innerHTML = decodeURIComponent( workorder.contactPerson );
    document.getElementById("tPhone").innerHTML = decodeURIComponent( workorder.contactPhone );
    document.getElementById("tCreated").innerHTML = ticketData.date_created;
    document.getElementById("tCreatedBy").innerHTML = decodeURIComponent( ticketData.user );
    document.getElementById("tDescription").innerHTML = decodeURIComponent( ticketData.description );
    document.getElementById("tCommentaar").innerHTML = decodeURIComponent( ticketData.comment );
    
    if( workorder.start_date == null ){ startDate = "Niet ingepland"; }
    else{ startDate = workorder.start_date };
    document.getElementById("tDateExec").innerHTML = decodeURIComponent( startDate );
    
    if( workorder.start_time == null ){ timeFrame = "Niet ingepland"; }
    else{ timeFrame = "Van " + workorder.start_time + " tot " + workorder.end_time };
    document.getElementById("tTimeExec").innerHTML = timeFrame;
    
    //Check if there were some pictures send
    if( ticketImages != false ) {    
        var callPics = runCallPics( ticketImages, workorder.id );
        document.getElementById('callPicsTd').innerHTML = callPics;
    }
    else {
        var callPics = "Geen foto's beschikbaar";
        document.getElementById('callPicsTd').innerHTML = callPics;
    }
}

//------------------------------------------------------------------------
function openKopPDFpage( workorderId ) {
    window.open('../controllers/CreateKopPDF.controller.php?wo='+workorderId, '_blank');
}

//------------------------------------------------------------------------
function openUrenPDFpage( workorderId ) {
    window.open('../controllers/CreateTicketPDF.controller.php?wo='+workorderId, '_blank');
}

//------------------------------------------------------------------------
function setWorkorderForm( formData )
{
    // Set the status bar
    setStatusBar( formData['workorder'].status_id );
    
    // Set short variable name workorderID for standard form functions.
    var workorderID = formData['workorder'].id;    
    var status   = formData['workorder'].status_id;
    var busID = formData['workorder'].executed_by;
    
    // Check whether the user has worknote rights
    if( formData['userSettings'].add_worknotes == "0" )
    { 
        document.getElementById('addDocTd').innerHTML = "";
        document.getElementById('uploadDocTd').innerHTML = "";
        document.getElementById('notesInputTd').innerHTML = "";
        document.getElementById('saveWorknotesTd').innerHTML = "";
    } else {
         // Set file attachent button
        var newBtn = document.createElement("button");
        newBtn.setAttribute("onclick", "attachDoc(" + workorderID + ")" );
        newBtn.innerHTML = "Bijlage toevoegen";
        document.getElementById("docsSpan").appendChild(newBtn);
        
        if( status < 5 ){ setBusSelector(workorderID, busID ); } 
        else { document.getElementById("saveWorknotesTd").innerHTML = ""; } 
    }
    
    // Set closing status button
    setClosingStatusButton(status, formData['userSettings'], workorderID );
    
    // Get the worknotes of this status
    getWorkNotes(workorderID, status, formData['userSettings'].add_worknotes );
    
    // Get the attached documents (status less)
    ListAttachments( formData['attachments'], workorderID );
}

//---------------------------------------------------------------
function setClosingStatusButton( status, userSettings, workorderID )
{ 
    if( userSettings.close_status !== "0" )
    {
        switch( status ) {
        //------------
            case "2" : 
                if( userSettings.confirm_workorder == "1" ) {
                    var btnText = "Werkopdracht goedkeuren";
                    buildCloseStatusButton( btnText, workorderID, status );
                } 
            break;
        //------------
            case "5" :  
                if( userSettings.archive_ticket == "1" ) {
                    var btnText = "Werkopdracht archiveren";
                    buildCloseStatusButton( btnText, workorderID, status );
                    document.getElementById('frmBtn').text = "Ticket afsluiten";
                } 
                
                //document.getElementById('docsSpan').innerHTML = "Ticket is afgesloten.";
                //document.getElementById('saveWorknotesTd').innerHTML = "";
               // document.getElementById('workNoteTa').disabled = true;
                    
                var invBtn = document.createElement("button");
                invBtn.setAttribute( 'onclick', 'invoiceForm(' + workorderID + ')');
                invBtn.innerHTML = "Factuur nummer toevoegen";
                document.getElementById('closesStatTd').prepend( invBtn );  
            break;
        //------------
            case "6" :
                document.getElementById('docsSpan').innerHTML = "Ticket is gearchiveerd.";
                document.getElementById('saveWorknotesTd').innerHTML = "";
                document.getElementById('closesStatTd').innerHTML = "";
                document.getElementById('workNoteTa').disabled = true;
            break;
        //------------
            default :
                var btnText = "Status afsluiten";
                buildCloseStatusButton( btnText, workorderID, status );
        }      
    } 
}

//------------------------------------------------------------------------
function buildCloseStatusButton( btnText, workorderID, status )
{
    var newBtn = document.createElement('button');
    newBtn.setAttribute('onclick', 'closeStatus(' + workorderID + ',' + status + ')');
    newBtn.setAttribute('id', 'frmBtn');
    newBtn.innerHTML = btnText;
    document.getElementById("closesStatTd").appendChild(newBtn);
}

//------------------------------------------------------------------------
function closeStatus( workorderID, statusID )
{
    if( statusID == "5" )
    {    
        var conf = confirm("Weet u zeker dat u deze werkopdracht wilt afsluiten?");
        if( conf == false ){ return; }
    }
    
    // Make sure, no other XMLHttpRequest is interfering
    XMLHttpRequestObjectCS = false;
    
    // Set new XMLHttpRequestObjectCS Object
    if(window.XMLHttpRequest) {
        XMLHttpRequestObjectCS = new XMLHttpRequest(); 
    } else if(window.ActiveXObject) {
        XMLHttpRequestObjectCS = new ActiveXObject("Microsoft.XMLHTTP");
    }
    
    // Connect the XMLHttpRequestObjectBUF with the Back-end file
    if( XMLHttpRequestObjectCS){
        XMLHttpRequestObjectCS.open("POST" , "../controllers/Workorder.controller.php");
        XMLHttpRequestObjectCS.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        // Verify the status of the XMLHttpRequestObjectCS
        XMLHttpRequestObjectCS.onreadystatechange = function()
        { 
            if( XMLHttpRequestObjectCS.readyState === 4  &&
                XMLHttpRequestObjectCS.status === 200)
            {    
                console.log( XMLHttpRequestObjectCS.responseText );

                if( XMLHttpRequestObjectCS.responseText == "false" ){
                    alert("Fout opetreden! De huidige status kan niet worden afgesloten. Probeert u het a.u.b. nogmaals. \n\nMocht dit probleem zich blijven voordoen, neem dan contact op met de Applicatiebeheerder.");
                }
                
                if( parseInt( statusID ) == 5 ){
                    window.location.href = "list.workorders.view.php";
                } else {
                    window.location.href = "workorder.view.php?wi="+workorderID;
                }  
            }
        }
        // Set data for invoking backend controller
        directPost = new Array();
        directPost[0] = "closeStatus";
        directPost[1] = workorderID;
        directPost[2] = statusID;

        // Send data array to Back-end
        XMLHttpRequestObjectCS.send("directPost=" + directPost);
    }   
}

//------------------------------------------------------------------------
function getWorkordersOfCustomer( pageId )
{
    var beMethod = "";
    var custSelect = document.getElementById("searchInput");   
    var customerId = custSelect.options[custSelect.selectedIndex].id;
    
    if( pageId == "3" ){ beMethod = "getOpenWorkordersOfCustomer"; }
    else if( pageId == "4" ){ beMethod = "getArchivedWorkordersOfCustomer"; }
    
    // Make sure, no other XMLHttpRequest is interfering
    XMLHttpRequestObjectGWOC = false;
    
    // Set new XMLHttpRequestObjectGWOC Object
    if(window.XMLHttpRequest) {
        XMLHttpRequestObjectGWOC = new XMLHttpRequest(); 
    } else if(window.ActiveXObject) {
        XMLHttpRequestObjectGWOC = new ActiveXObject("Microsoft.XMLHTTP");
    }
    
    // Connect the XMLHttpRequestObjectGWOC with the Back-end file
    if( XMLHttpRequestObjectGWOC){
        XMLHttpRequestObjectGWOC.open("POST" , "../controllers/Workorder.controller.php");
        XMLHttpRequestObjectGWOC.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        XMLHttpRequestObjectGWOC.onreadystatechange = function()
        { 
            if( XMLHttpRequestObjectGWOC.readyState === 4  &&
                XMLHttpRequestObjectGWOC.status === 200)
            {    
                console.log("cust Wo Resp: " + XMLHttpRequestObjectGWOC.responseText );
                var custWoResp = JSON.parse(XMLHttpRequestObjectGWOC.responseText);
                if( custWoResp == false )
                {    
                    if( pageId == "3" ){
                        alert("Geen open werkopdrachten gevonden voor deze klant");
                        getOpenWorkorders();
                    }
                    else if( pageId == "4" ){
                        alert("Geen gearchiveerde werkopdrachten gevonden voor deze klant");
                        getArchivedWorkorders();
                    }
                } else {
                    buildWorkordersOverview( custWoResp );    
                }
            }
        }
        // Set data for invoking backend controller
        directPost = new Array();
        directPost[0] = beMethod;
        directPost[1] = customerId;

        // Send data array to Back-end
        XMLHttpRequestObjectGWOC.send("directPost=" + directPost);
    }
    
}