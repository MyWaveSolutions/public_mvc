import React from "react";
import { Image, Text, TouchableHighlight, View } from "react-native";
import styles from '../styles/mainStyles';
export default class main extends React.Component {

    render() {

        // Icons for screen
        let picLogo = {
            uri : 'https://demo.trialscan.app/assets/appImages/Trialscan_logo_app.gif' }
        let picPlantIcon = {
            uri : 'https://demo.trialscan.app/assets/appImages/plant_icon.png' }
        let picMemoIcon = {
            uri : 'https://demo.trialscan.app/assets/appImages/memo_icon.png' }
        let picReportIcon = {
            uri : 'https://demo.trialscan.app/assets/appImages/reports_icon.png' }
        let picBreederIcon = {
            uri : 'https://demo.trialscan.app/assets/appImages/growers_icon.jpg' }
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
                <TouchableHighlight style={ styles.container} onPress={() => this.props.navigation.navigate('Plants')}>
                    <View style={ styles.container}>
                        <Image style={styles.pageMainIcon}  source={picPlantIcon} />
                        <Text style={styles.pageMainText}>Plants</Text>
                        <Image style={styles.pageNextIcon}  source={picNextIcon} />
                    </View>
                </TouchableHighlight>
                <TouchableHighlight style={ styles.container } onPress={() => this.props.navigation.navigate('Memos')}>
                    <View style={ styles.container}>
                        <Image style={styles.pageMainIcon} source={picMemoIcon} />
                        <Text style={styles.pageMainText}>Memos</Text>
                        <Image style={styles.pageNextIcon}  source={picNextIcon} />
                    </View>
                </TouchableHighlight>
                <TouchableHighlight style={ styles.container } onPress={() => this.props.navigation.navigate('Reports')}>
                    <View style={ styles.container}>
                        <Image style={styles.pageMainIcon} source={picReportIcon} />
                        <Text style={styles.pageMainText}>Reports</Text>
                        <Image style={styles.pageNextIcon}  source={picNextIcon} />
                    </View>
                </TouchableHighlight>
                <TouchableHighlight  style={ styles.container } onPress={() => this.props.navigation.navigate('Breeders')}>
                    <View style={ styles.container}>
                        <Image style={styles.pageMainIcon} source={picBreederIcon} />
                        <Text style={styles.pageMainText}>Breeders</Text>
                        <Image style={styles.pageNextIcon}  source={picNextIcon} />
                    </View>
                </TouchableHighlight>
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