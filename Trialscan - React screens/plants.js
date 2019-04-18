import React from 'react';
import {ActivityIndicator, Alert, Image, Text, TextInput, View} from 'react-native';
import { Button } from 'react-native-elements';
import styles from "../styles/mainStyles";
import memoStyles from "../styles/memoStyle";

export default class plants extends React.Component {

    constructor( props ) {
        super( props );
        this.state = { plantCode : '' };
        this.state = { isLoading: false };
        this.state = { plantName : '' };
        this.state = { plantBreeder : '' };
        this.state = { plantImgUri : '../asset/icon.png' }
    }

    fetchData = () => {
        fetch('https://demo.trialscan.nl/api/plant.php',
            {
                method: 'POST',
                headers: new Headers({
                    'Content-Type': 'application/x-www-form-urlencoded',
                }),
                body: "code="+this.state.plantCode
            })

        .then( ( response ) => response.json() )
        .then( ( responseJson ) => {

            this.setState({
                plantName : responseJson.plant.name,
                plantBreeder : responseJson.plant.breeder,
                plantImgUri :  responseJson.plant.image_url,
            }, function(){ } );
        })
        .catch( ( error ) => { console.error( error ) } );
    }

    render() {
        let picNotesIcon = {
            uri : 'https://demo.trialscan.app/assets/appImages/notes_icon.jpg' }
        let picDetailsIcon = {
            uri : 'https://demo.trialscan.app/assets/appImages/plant_detail.png' }
        let picGrowthIcon = {
            uri : 'https://demo.trialscan.app/assets/appImages/growInfo.png'}
        let picNextIcon = {
            uri : 'https://demo.trialscan.app/assets/appImages/arrow_bold.png' }
        let HomeIcon = {
            uri : 'https://demo.trialscan.app/assets/appImages/web_home.png' }
        let WebsiteIcon = {
            uri : 'https://demo.trialscan.app/assets/appImages/web_icon.png' }
        let ContactIcon = {
            uri : 'https://demo.trialscan.app/assets/appImages/web_support.png' }
        let PrivacyIcon = {
            uri : 'https://demo.trialscan.app/assets/appImages/web_privacy.png' }

        return (
            <View style={{
                flex: 1,
                flexDirection : 'column',
                justifyContent: 'space-between'
            }}>
                <View style={{flex:3}}>
                    <Image style={{ backgroundColor: '#E6E6E6', flex :1, resizeMode: 'contain', }} source={{ uri : this.state.plantImgUri }} />
                </View>
                <View style={{alignItems: 'center', flex:1, flexDirection : 'row', margin : 10 }}>
                    <Text style={{ flex: 1}} >Plant code:</Text>
                    <Text>TS-</Text>
                    <TextInput
                        style={{ flex: 2, height:40, padding: 5, borderWidth : 2, borderColor: '#70AE6C'}}
                        returnKeyType='search'
                        autoFocus = { true }
                        onChangeText={(input) => this.setState({plantCode: input })}
                        onSubmitEditing = { a => this.fetchData() }
                    />
                </View>
                <View style={{ flex:1, borderTopWidth: 2, borderTopColor: '#70AE6C'}}>
                    <Text style={{ height: 20, margin : 10, flex:2, fontSize:12 }}>
                          Plant name : {this.state.plantName }
                    </Text>
                        <Text style={{ height: 20, margin : 10, flex:2, fontSize:12 }}>
                            Breeder : {this.state.plantBreeder }
                        </Text>
                </View>
                <View>
                    <Button icon = {{ name: 'note-add', color : 'white' }}
                            style = { memoStyles.saveMemoButton }
                            title = "Personal notes"
                            onPress = { this.saveMemo }
                    />
                    <Button icon = {{ name: 'note', color : 'white' }}
                            style = { memoStyles.saveMemoButton }
                            title = "Plant details"
                            onPress = { this.saveMemo }
                    />
                    <Button icon = {{ name: 'info', color : 'white' }}
                            style = { memoStyles.saveMemoButton }
                            title = "Growth information"
                            onPress = { this.saveMemo }
                    />
                </View>
                <View style={ styles.pageFooter  }>
                    <View style={styles.pageFooterView}>
                        <Image style={styles.footerIcon} source={HomeIcon} />
                        <Text style={{color:'white'}}>Home</Text>
                    </View>
                    <View style={styles.pageFooterView}>
                        <Image style={styles.footerIcon} source={WebsiteIcon} />
                        <Text style={{color:'white'}}>Website</Text>
                    </View>
                    <View style={styles.pageFooterView}>
                        <Image style={styles.footerIcon} source={ContactIcon} />
                        <Text style={{color:'white'}}>Contact</Text>
                    </View>
                    <View style={styles.pageFooterView}>
                        <Image style={styles.footerIcon} source={PrivacyIcon} />
                        <Text style={{color:'white'}}>Privacy</Text>
                    </View>
                </View>
            </View>
        );
    }
}