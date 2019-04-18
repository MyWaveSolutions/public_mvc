import React from 'react';
import { Alert, Image, FlatList, Text, TextInput, View, WebView} from 'react-native';
import { Button } from 'react-native-elements';
import reportStyles from '../styles/reportsStyles';
import styles from "../styles/mainStyles";
import memoStyles from "../styles/memoStyle";

export default class report extends React.Component {

    //-------------------------------------------------------------------------
    /**
     * Instantiate all all App props
     * @param props
     */
    constructor( props ) {
        super( props );
        this.state = { changedPlantId : null };
        this.state = { compId : "" };
        this.state = { newComment : null };
        this.state = { reportMemos : null };
        this.state = { reportScans : null };
        this.state = { selectedPlantId : null };
    }

    //-------------------------------------------------------------------------
    /**
     * First thing to do: get the company ID that was passed by reports screen.
     * @returns {Promise<void>}
     */
    async componentWillMount()
    {
        const {navigation } = this.props;
        const compIdValue = navigation.getParam('compId');
        this.setState({ compId : compIdValue })
    }

    //-------------------------------------------------------------------------
    async componentDidMount()
    {
        await this.getAllReportItems();
    }

    //-------------------------------------------------------------------------
    getAllReportItems = async () =>
    {
        /**
         * Fill Report Request body with source identification flags and company
         * ID for the selected report items
         * @type {FormData}
         */
        let reportRequestBody = new FormData();
        reportRequestBody.append('formPost', 'reportForm');
        reportRequestBody.append('source', 'mobileApp');
        reportRequestBody.append('method', 'getItemsOfReport');
        reportRequestBody.append('companyId', this.state.compId);
        reportRequestBody.append('userId', 1);

        const reqOpt = {
            method: 'POST',
            body: reportRequestBody,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'multipart/form-data',
            },
        };

        let reportRequestResp = await fetch(
            'https://demo.trialscan.nl/api/reports.php', reqOpt
        );

        /**
         * Wait untill all report items has been loaded into the app
         * @type {any}
         */
        let reportJSON = await reportRequestResp.json();

        /**
         * If the returned array is "reportItems" assign to state reportItems
         */
        if( reportJSON.reportItems ){
            this.setState({ reportScans : reportJSON.reportItems['scans'] });
            this.setState({ reportMemos : reportJSON.reportItems['memos'] });

        /**
         *  If the returned array is "Error" show the error and notify the user.
         */
        } else if ( reportJSON.error ){
            Alert.alert("Server side Error occurred. check the Console log");
            console.error( reportJSON.error );
        /**
         * If the array is different than "reportItems" and "error" notify the user
         * that there is a local issue.
         */
        } else {
            Alert.alert("Fatal Error occurred. check the Console log");
            console.error( reportJSON.error );
        }

        console.log(this.state.reportScans);
    }

    //-------------------------------------------------------------------------
    /** Send plantId and user id to server for removal. **/
    async handleScanDeletePress( scanId ) {

        const delUriApi = 'https://demo.trialscan.nl/api/reports.php';

        let reqBody = new FormData();
        reqBody.append('formPost', 'reportForm');
        reqBody.append('source', 'mobileApp');
        reqBody.append('method', 'deleteScannedItemOfReport');
        reqBody.append('scanId', scanId);
        reqBody.append('userId', 1);

        const delOpt = {
            method : 'POST',
            body : reqBody,
            header : {
                Accept: 'application/json',
                'Content-Type' : 'multipart/form-data'
            }
        };

        let delResp = await fetch( delUriApi, delOpt );
        let respText = await delResp.json();
        console.log("---");
        console.log(respText);
        console.log("---");

        if( respText === "OK" )
        {
            /** Confirm the delete the the user **/
            Alert.alert("Scanned item has been removed");

            /** Reload the list again (update after removal) **/
            this.getAllReportItems();

        } else {
            Alert.alert("Error deleting scanned item");
        }

    }

    //-------------------------------------------------------------------------
    render() {

        let HomeIcon = {
            uri : 'https://demo.trialscan.app/assets/appImages/web_home.png' };
        let WebsiteIcon = {
            uri : 'https://demo.trialscan.app/assets/appImages/web_icon.png' };
        let ContactIcon = {
            uri : 'https://demo.trialscan.app/assets/appImages/web_support.png' };
        let PrivacyIcon = {
            uri : 'https://demo.trialscan.app/assets/appImages/web_privacy.png' };

        return (
            <View style={reportStyles.container}>
                <View style={{flex:1, alignItems: 'stretch', margin : 0}}>
                    <FlatList
                        data={this.state.reportScans}
                        keyExtractor={(item, index) => item.id}
                        renderItem={({ item }) =>
                            <View style={reportStyles.rowContainer}>
                                <View style={reportStyles.container_details}>
                                    <Image source={{ uri: item.imgUrl }} style={reportStyles.photo} />
                                    <View style={reportStyles.container_text}>
                                        <Text style={reportStyles.title}>
                                            { decodeURIComponent( item.name )} { decodeURIComponent( item.variety )}
                                        </Text>
                                        <WebView style={{ width: '100%', height: 15 }}
                                            source={{html: "<h1>"+ item.color + "</h1>" }}
                                            scalesPageToFit={true}
                                        />
                                        <Text style={reportStyles.description}>
                                            Plant code : {item.plants_id}
                                        </Text>
                                        <Text style={reportStyles.description}>
                                            Time scanned : {item.date_created}
                                        </Text>
                                    </View>
                                </View>
                                <View style={reportStyles.textInputView}>
                                    <TextInput
                                        editable={false}
                                        multiline={true}
                                        style={reportStyles.multilineInput}
                                        value={decodeURIComponent( item.notes )}
                                    />
                                    <Button
                                        icon = {{ name: 'delete', color : 'white' }}
                                        style = { reportStyles.photoButton }
                                        title = "Delete scanned item"
                                        onPress = { () => {
                                            this.handleScanDeletePress( item.id )
                                        }}
                                    />
                                </View>
                            </View>
                        }
                    />
                </View>
                <View style={ styles.pageFooter  }>
                    <View style={ styles.pageFooterView }>
                        <Image style={ styles.footerIcon } source={HomeIcon} />
                        <Text style={ styles.pageFooterText }>Home</Text>
                    </View>
                    <View style={ styles.pageFooterView }>
                        <Image style={ styles.footerIcon } source={WebsiteIcon} />
                        <Text style={ styles.pageFooterText }>Website</Text>
                    </View>
                    <View style={ styles.pageFooterView }>
                        <Image style={ styles.footerIcon } source={ContactIcon} />
                        <Text style={ styles.pageFooterText }>Contact</Text>
                    </View>
                    <View style={ styles.pageFooterView }>
                        <Image style={ styles.footerIcon } source={PrivacyIcon} />
                        <Text style={ styles.pageFooterText }>Privacy</Text>
                    </View>
                </View>
            </View>
        );
    }
}
