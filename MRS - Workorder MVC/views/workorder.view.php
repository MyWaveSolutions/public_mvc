<?php

session_start();

# Default configuration 
include("../config/config.php");
include('../lib/Security.class.php');
include('../lib/Ticket.class.php');
include('../lib/User.class.php');

$ACCESS = new Security( $ERR, $LOG );

if( $ACCESS->checkCurrentUserSession() !== "true" )
{ header('Location: ../views/login.view.php'); }

$USER = new USER( $ERR, $LOG );
$USERDATA = $USER->getUserById( $_SESSION['USER_ID'] );
$activeUser = $USERDATA['firstname']." ".$USERDATA['lastname'];
$serverTime = date("d-m-Y H:i:s");
?>

<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="content-type" content="text/html" />
	<meta name="author" content="MyWAVE Solutions" />
    <link rel="stylesheet" type="text/css" href="../css/style.css">
    <script src="../js/attachDoc.js"></script>
    <script src="../js/bus.controller.js"></script>
    <script src="../js/invoice.controller.js"></script> 
    <script src="../js/navigationScript.js"></script> 
    <script src="../js/statusBarScript.js"></script>
    <script src="../js/ticketScript.js"></script>
    <script src="../js/worknotes.controller.js"></script> 
    <script src="../js/workorder.create.controller.js"></script>
    <script src="../js/workorder.controller.js"></script> 
    <script>
        var activeUser = '<?php echo $activeUser; ?>';
        var serverTime = '<?php echo $serverTime; ?>';
    </script>
        <script>
        
        function getUrlVars() {
            var vars = {};
            var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
            vars[key] = value;
            });
            return vars;
        }
        
        var workorderId = getUrlVars()['wi'];
    </script>
	<title>SRS - Werkopdracht</title>
    
</head>

<body>
<div id="headerDiv"></div>
<div id="contentDiv">
    <div id="navigationDiv"></div>
    <div id="statusDiv"></div>
    <div id="attachDiv"></div>
    <div id="formDiv">
        <button id="printWoBtn" onclick="openWorkorderPDFpage()">Werkopdracht Printen / Downloaden</button>
        <!-- Woknotes Div -->
        <div id='worknotesDiv'>       
            <table id='worknotesTbl'>
                <tr><td id='addDocTd'>
                        <span id='docsSpan'></span>
                        <span id='docLinks'></span>
                        <span id='invSpan'></span></td>
                    <td id='closesStatTd'></td>
                </tr>
                <tr><td colspan='3' id='uploadDocTd'></td></tr>
                <tr><td colspan='3' id='notesInputTd'><br>Voeg werknotitie toe..<br>
                    <textarea onkeyup='textAreaAdjust(this)' style='overflow:hidden' id='workNoteTa'></textarea><td>
                </tr>
                <tr><td colspan='3' id='saveWorknotesTd'>
                    <span id="assignBusSpan">
                        <select id="busSelect"></select>
                    </span>
                    </td></tr>
                <tr><td colspan='3' id='line'><hr></td></tr>
                <tr><td colspan='3' id='worknotesHeadTd'>Overzicht werknotities</td></tr>
                <tr><td colspan='3' id='invoiceTr'></td></tr>
                <tr><td colspan='3' id='worknotesTd'></td></tr>
            </table>
        </div>
        
        <!-- Original call -->
        <div id='originalCallDiv'>
        <span id='callHeadSpan'>UITVOERINGSBON</span>
        <span id='printPdfSpan'></span>
        <br><br>
        <table id='originalCallTbl'>
            <tr><td>Werkopdracht:</td><td id='tWoID'></td></tr>
            <tr><td>Klant:</td><td id='tCustomer'></td></tr>
            <tr><td>Locatie:</td><td id='tLocation'></td></tr>
            <tr><td>Contact:</td><td id='tContactPers'></td></tr>
            <tr><td>Tel:</td><td id='tPhone'></td></tr>
            <tr><td id="dateLabel">Aangemaakt:</td><td id='tCreated'></td></tr>
            <tr><td id="createdByLabel">Aangemaakt door:</td><td id='tCreatedBy'></td></tr>
            <tr><td>Datum uitvoering:</td><td id='tDateExec'></td></tr>
            <tr><td>Tijden uitvoering:</td><td id='tTimeExec'></td></tr>
            <tr><td class='callDetailsTd' colspan='2'><hr></td></tr>
            <tr><td class='callDetailsTd' colspan='2'>Omschrijving: <span id='tDescription'></span></td></tr>
            <tr><td class='callDetailsTd' colspan='2'>Opmerkingen: <span id='tCommentaar'></span></td></tr>
            <tr><td colspan='2'><br><br><b>STORING FOTO'S</b></td></tr>
            <tr><td colspan='2' id='callPicsTd'></td></tr>
            </table></div>
    </div>

</div>

<!-- Per default set header and new contact form -->
<script>
    getWorkOrderData( workorderId );
    setPageHeader(0);
    
    //------------------------------------------------------------------------
    function openWorkorderPDFpage() {
        window.open('../controllers/CreateWorkorderPDF.controller.php?wo='+workorderId, '_blank');
    }
</script> 

</body>
</html>