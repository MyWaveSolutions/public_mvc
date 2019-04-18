import React from 'react';
import {Alert, FlatList, Image, Navigator, Text, TouchableOpacity, View} from 'react-native';
import reportStyles from '../styles/reportsStyles';
import styles from "../styles/mainStyles";

export default class reports extends React.Component {

    constructor( props ){
        super( props);
        this.state = { dataSource : false };
    }

    //-------------------------------------------------------------------------
    async componentDidMount() {
        await this.getVisitedCompanies();
    };

    //-------------------------------------------------------------------------
    getVisitedCompanies = async () => {
        /**
         * See memo.js file for comments on this method process
         * @type {{method: string, headers: Headers | any, body: string}}
         */
        let reqOpt = {
            method : 'POST',
            headers : new Headers({
                'Content-Type' : 'application/x-www-form-urlencoded'
            }),
            body: "method=getVisitedCompanies"
        };

        let visitsResp = await fetch(
            'https://demo.trialscan.nl/api/reports.php', reqOpt
        );

        console.log(visitsResp );
        let visitsJSON = await visitsResp.json();
        this.setState({ dataSource : visitsJSON.companies });
    };

    // ------------------------------------------------------------------------
    render()
    {
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
                <View style={reportStyles.titleView}>
                    <Text style={reportStyles.titleText} >Your visits Reports </Text>
                </View>
                <View style={styles.container}>
                    <View style={{flex:1}}>
                        <FlatList
                            data={this.state.dataSource}
                            keyExtractor={(item, index) => item.name}
                            renderItem={({ item }) =>
                                <View style={reportStyles.rowContainer}>
                                    <TouchableOpacity onPress={ () => {
                                        this.props.navigation.navigate('Report', {'compId' : item.id} )
                                    }}>
                                        <Image source={{ uri: item.logoUrl }} style={reportStyles.photo} />
                                        <View style={reportStyles.container_text}>
                                            <Text style={reportStyles.title}>
                                                {item.name}
                                            </Text>
                                        </View>
                                    </TouchableOpacity>
                                </View>
                            }
                        />

                    </View>
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

