'use strict';
import React from 'react';
import {ActivityIndicator,
        Alert,
        Image,
        Keyboard,
        Picker,
        Text, TextInput,
        View }
        from 'react-native';
import { Button } from 'react-native-elements';
import { ImagePicker, Permissions} from 'expo';
import styles from "../styles/mainStyles";
import memoStyles from "../styles/memoStyle";

export default class memos extends React.Component
{
    //-------------------------------------------------------------------------
    constructor(props) {
        super(props);
        this.state = { company : "" };
        this.state = { compList : "" };
        this.state = { dataSource :false };
        this.state = { hasCameraPermission :null };
        this.state = { memoText : "" };
        this.state = { imageSource : null };
        this.state = { isLoading : true };
        this.state = { userPhoto : false };
        this.state = { memoSubmit : "Save memo" };
    }

	//------------------------------------------------------------------------
    async componentDidMount() { /** is called after render took place **/
        /**
         * Make sure the life cycle waits until function getCompanies is done
         */
        await this.getCompanies();

        /** Once all getCompanies is done, create a Picker items list from the
         * dataSource state that was set by getCompanies. That's why this
         * function must wait till getCompanies is finished, otherwise error
         * will occurr, since state dataSource was not set (e.g. not done)
         **/
        let companyItems = this.state.dataSource.map((item, key)=>(
            <Picker.Item label={item.name} value={item.id} key={key} /> ));

        /**
         * Write the Picker items list to a state, in order to have the picker
         * component updated by this function.
         */
        this.setState({ compList : companyItems });
    }

    //------------------------------------------------------------------------
    getCompanies = async () => {

        /**
         * Set the required options for backend controller in orde to return
         * the correct and expected dataset
         * @type {{method: string, headers: Headers | any, body: string}}
         */
        const compOpt = {
            method: 'POST',
            headers: new Headers({
                'Content-Type': 'application/x-www-form-urlencoded',
            }),
            body: "method=getCompanies"
        };

        /**
         * Make sure the lifcycle waits untill all data is retrieved.
         * @type {Response}
         */
        let compResp = await fetch('https://demo.trialscan.nl/api/companies.php', compOpt);
        let compJson = await compResp.json();

        /**
         * We must first set the state dataSource with the retrieved data before
         * we can continue. if we do not wait,  dataSource will be set with undefined
         */
        this.setState({ dataSource : compJson.companies });
    };

    //------------------------------------------------------------------------
    askPermissionsAsync = async () => {
        await Permissions.askAsync(Permissions.CAMERA);
        await Permissions.askAsync(Permissions.CAMERA_ROLL);
    };

    //------------------------------------------------------------------------
    useLibraryHandler = async () => {
        await this.askPermissionsAsync();
        let result = await ImagePicker.launchImageLibraryAsync({
            allowsEditing: true,
            aspect: [4, 3],
            base64: false,
        });
        if( result !== undefined ){
            this.setState({ imageSource: result });
        }
    };

    //------------------------------------------------------------------------
    useCameraHandler = async () => {
        await this.askPermissionsAsync();
        let result = await ImagePicker.launchCameraAsync({
            allowsEditing: true,
            aspect: [4, 3],
            quality: 1,
            base64: false,
        });
        this.setState({ imageSource: result });
    };

    //-------------------------------------------------------------------------
    saveMemo = async () => {

        this.setState.memoSubmit = "Sending...";
        const uriApi = "https://demo.trialscan.nl/api/memos.php";

        let memoBody = new FormData();
        memoBody.append( 'formPost', 'memoForm' );
        memoBody.append( 'source', 'mobileApp' );
        memoBody.append( 'memoComp', this.state.company );
        memoBody.append( 'memoTextarea', this.state.memoText );
        memoBody.append( 'userId', 1 );

        /**
         * Make sure an image was taken or chosen. If this is not checked, the
         * app will display an error or crash once being operational.
         */
        if( this.state.imageSource !== undefined ) {
            const uri = this.state.imageSource.uri;
            const uriParts = uri.split('.');
            const fileType = uriParts[uriParts.length - 1];

            memoBody.append('photo', {
                uri,
                name: `photo.${fileType}`,
                type: `image/${fileType}`,
            });
        }

        const options = {
            method: 'POST',
            body: memoBody,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'multipart/form-data',
            },
        };

        fetch(uriApi, options )
        .then( ( response ) => response.json() )
        .then( ( responseJson ) => {
            Alert.alert( "Memo has been save successfully" );
        })
        .catch( ( error ) => { console.error( error ) } );
    }

    //-------------------------------------------------------------------------
    render()
    {
        if( this.state.isLoading ){
            return (
                <View style={{ flex:1, padding:20 }}>
                    <ActivityIndicator/>
                </View>
            )
        }

        let HomeIcon = {
            uri : 'https://demo.trialscan.app/assets/appImages/web_home.png' };
        let WebsiteIcon = {
            uri : 'https://demo.trialscan.app/assets/appImages/web_icon.png' };
        let ContactIcon = {
            uri : 'https://demo.trialscan.app/assets/appImages/web_support.png' };
        let PrivacyIcon = {
            uri : 'https://demo.trialscan.app/assets/appImages/web_privacy.png' };

        return (
            /** Main view : Holds all other views and components **/
            <View style={ memoStyles.mainView}>
                <View style={ memoStyles.photoView } >
                    <Image style= { memoStyles.image  }
                           source={this.state.imageSource != null ? this.state.imageSource :
                               require('../assets/not_available.jpg')}
                    />
                    <Button icon = {{ name: 'camera-enhance', color : 'white' }}
                            style = { memoStyles.photoButton }
                            title = "Take photo"
                            onPress = { this.useCameraHandler}
                    />
                    <Button icon = {{ name: 'perm-media', color : 'white' }}
                            style = { memoStyles.photoButton }
                            title = "Choose photo"
                            onPress = { this.useLibraryHandler }
                    />
                </View>

                <View style={memoStyles.selectCompanyView} >
                    <Text style={memoStyles.selectCompanyText} >Select company</Text>
                </View>
                <View style={ memoStyles.companyPickerView }>

                    <Picker style= { memoStyles.companyPicker }
                        selectedValue = {this.state.company }
                        onValueChange={(itemValue ) => this.setState({company: itemValue})} >
                        { this.state.compList }
                    </Picker>
                </View>

                <View style={ memoStyles.memoTextView  }>
                    <TextInput
                        style={ memoStyles.memoTextArea }
                        multiline = {true}
                        numberOfLines = {4}
                        onSubmitEditing = { a => Keyboard.dismiss() }
                        onChangeText={ ( memoText ) => this.setState({ memoText }) }
                        placeholder = "Type memo..."
                        value={ this.state.memoText }
                        />
                </View>

                <View style={ memoStyles.saveView }>
                    <Button icon = {{ name: 'save', color : 'white' }}
                            style = { memoStyles.saveMemoButton }
                            title = "Save memo"
                            onPress = { this.saveMemo }
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
